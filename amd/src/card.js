import {DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';
import {saveCancel} from 'core/notification';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Str from 'core/str';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import KanbanComponent from 'mod_kanban/kanbancomponent';


/**
 * Component representing a card in a kanban board.
 */
export default class extends KanbanComponent {
    /**
     * For relative time helper.
     */
    _units = {
        year: 24 * 60 * 60 * 1000 * 365,
        month: 24 * 60 * 60 * 1000 * 365 / 12,
        day: 24 * 60 * 60 * 1000,
        hour: 60 * 60 * 1000,
        minute: 60 * 1000,
        second: 1000
    };

    /**
     * Function to initialize component, called by mustache template.
     * @param {*} target The id of the HTMLElement to attach to
     * @returns {BaseComponent} New component attached to the HTMLElement represented by target
     */
    static init(target) {
        let element = document.getElementById(target);
        return new this({
            element: element,
        });
    }

    /**
     * Called after the component was created.
     */
    create() {
        this.id = this.element.dataset.id;
    }

    /**
     * Watchers for this component.
     * @returns {array} All watchers for this component
     */
    getWatchers() {
        return [
            {watch: `cards[${this.id}]:updated`, handler: this._cardUpdated},
            {watch: `cards[${this.id}]:deleted`, handler: this._cardDeleted},
            {watch: `discussions:created`, handler: this._discussionUpdated},
            {watch: `discussions:updated`, handler: this._discussionUpdated},
            {watch: `discussions:deleted`, handler: this._discussionUpdated},
            {watch: `history:created`, handler: this._historyUpdated},
            {watch: `history:updated`, handler: this._historyUpdated},
            {watch: `history:deleted`, handler: this._historyUpdated},
        ];
    }

    /**
     * Called once when state is ready (also if component is registered after initial state was set), attaching event
     * isteners and initializing drag and drop.
     * @param {*} state The initial state
     */
    stateReady(state) {
        // Get language for relative time formatting.
        let lang = 'en';
        if (state.common.lang !== undefined) {
            lang = state.common.lang;
        }
        this.rtf = new Intl.RelativeTimeFormat(lang, {numeric: 'auto'});

        this.addEventListener(
            this.getElement(selectors.DELETECARD, this.id),
            'click',
            this._removeConfirm
        );
        this.addEventListener(
            this.getElement(selectors.ADDCARD, this.id),
            'click',
            this._addCard
        );
        this.addEventListener(
            this.getElement(selectors.COMPLETE, this.id),
            'click',
            this._completeCard
        );
        this.addEventListener(
            this.getElement(selectors.UNCOMPLETE, this.id),
            'click',
            this._uncompleteCard
        );
        this.addEventListener(
            this.getElement(selectors.ASSIGNSELF, this.id),
            'click',
            this._assignSelf
        );
        this.addEventListener(
            this.getElement(selectors.UNASSIGNSELF, this.id),
            'click',
            this._unassignSelf
        );
        this.addEventListener(
            this.getElement(selectors.EDITDETAILS, this.id),
            'click',
            this._editDetails
        );
        this.addEventListener(
            this.getElement(selectors.DISCUSSIONMODALTRIGGER),
            'click',
            this._updateDiscussion
        );
        this.addEventListener(
            this.getElement(selectors.DISCUSSIONSHOW, this.id),
            'click',
            this._updateDiscussion
        );
        this.addEventListener(
            this.getElement(selectors.DISCUSSIONSEND),
            'click',
            this._sendMessage
        );
        this.addEventListener(
            this.getElement(selectors.HISTORYMODALTRIGGER),
            'click',
            this._updateHistory
        );
        this.addEventListener(
            this.getElement(selectors.MOVEMODALTRIGGER),
            'click',
            this._showMoveModal
        );

        this.draggable = false;
        this.dragdrop = new DragDrop(this);
        this.checkDragging(state);
        this.boardid = state.board.id;
        this.cmid = state.common.id;
        this.userid = state.board.userid;
        this.groupid = state.board.groupid;
        this._dueDateFormat();
    }

    /**
     * Show modal to move a column.
     */
    _showMoveModal() {
        let data = exporter.exportStateForTemplate(this.reactive.state);
        data.cardid = this.id;
        data.kanbancolumn = this.reactive.state.cards.get(this.id).kanban_column;
        Str.get_strings([
            {key: 'movecard', component: 'mod_kanban'},
            {key: 'move', component: 'core'},
        ]).then(function(strings) {
            saveCancel(
                strings[0],
                Templates.render('mod_kanban/movemodal', data),
                strings[1],
                () => {
                    let column = document.querySelector(selectors.MOVECARDCOLUMN + `[data-id="${this.id}"]`).value;
                    let aftercard = document.querySelector(selectors.MOVECARDAFTERCARD + `[data-id="${this.id}"]`).value;
                    this.reactive.dispatch('moveCard', this.id, column, aftercard);
                }
            );
        });
    }

    /**
     * Display confirmation modal for deleting a card.
     * @param {*} event
     */
    _removeConfirm(event) {
        Str.get_strings([
            {key: 'deletecard', component: 'mod_kanban'},
            {key: 'deletecardconfirm', component: 'mod_kanban'},
            {key: 'delete', component: 'core'},
        ]).then(function(strings) {
            saveCancel(
                strings[0],
                strings[1],
                strings[2],
                () => {
                    this._removeCard(event);
                }
            );
        });
    }

    /**
     * Display confirmation modal for deleting a discussion message.
     * @param {*} event
     */
    _removeMessageConfirm(event) {
        Str.get_strings([
            {key: 'deletemessage', component: 'mod_kanban'},
            {key: 'deletemessageconfirm', component: 'mod_kanban'},
            {key: 'delete', component: 'core'},
        ]).then(function(strings) {
            saveCancel(
                strings[0],
                strings[1],
                strings[2],
                () => {
                    this._removeMessage(event);
                }
            );
        });
    }

    /**
     * Dispatch event to add a message to discussion.
     */
    _sendMessage() {
        let el = this.getElement(selectors.DISCUSSIONINPUT);
        let message = el.value.trim();
        if (message != '') {
            this.reactive.dispatch('sendDiscussionMessage', this.id, message);
            el.value = '';
        }
    }

    /**
     * Dispatch event to update the discussion data.
     */
    _updateDiscussion() {
        this.getElement(selectors.DISCUSSIONMODAL).classList.add('mod_kanban_loading');
        this.reactive.dispatch('getDiscussionUpdates', this.id);
    }

    /**
     * Called when discussion was updated.
     */
    async _discussionUpdated() {
        let data = {
            discussions: exporter.exportDiscussion(this.reactive.state, this.id)
        };
        Templates.renderForPromise('mod_kanban/discussionmessages', data).then(({html}) => {
            this.getElement(selectors.DISCUSSION, this.id).innerHTML = html;
            this.getElement(selectors.DISCUSSIONMODAL, this.id).classList.remove('mod_kanban_loading');
            let el = this.getElement(selectors.DISCUSSIONMESSAGES);
            // Scroll down to latest message.
            el.scrollTop = el.scrollHeight;
            data.discussions.forEach((d) => {
                this.addEventListener(this.getElement(selectors.DELETEMESSAGE, d.id), 'click', this._removeMessageConfirm);
            });
            return true;
        }).catch((error) => displayException(error));
    }

    /**
     * Dispatch event to update the history data.
     */
    _updateHistory() {
        this.getElement(selectors.HISTORYMODAL).classList.add('mod_kanban_loading');
        this.reactive.dispatch('getHistoryUpdates', this.id);
    }

    /**
     * Called when history was updated.
     */
    async _historyUpdated() {
        let data = {
            historyitems: exporter.exportHistory(this.reactive.state, this.id)
        };
        Templates.renderForPromise('mod_kanban/historyitems', data).then(({html}) => {
            this.getElement(selectors.HISTORY, this.id).innerHTML = html;
            this.getElement(selectors.HISTORYMODAL).classList.remove('mod_kanban_loading');
            // Scroll down to latest history item.
            let el = this.getElement(selectors.HISTORYITEMS);
            el.scrollTop = el.scrollHeight;
            return true;
        }).catch((error) => displayException(error));
    }

    /**
     * Dispatch event to assign the current user to the card.
     * @param {*} event
     */
    _assignSelf(event) {
        let target = event.target.closest(selectors.ASSIGNSELF);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('assignUser', data.id);
    }

    /**
     * Dispatch event to add a card after this card.
     * @param {*} event
     */
    _addCard(event) {
        document.activeElement.blur();
        let target = event.target.closest(selectors.ADDCARD);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('addCard', data.columnid, data.id);
    }

    /**
     * Called when card is updated.
     * @param {*} param0
     */
    async _cardUpdated({element}) {
        const card = this.getElement();
        // Card was moved to another column. Move the element to new card (right position is handled by column component).
        if (card.dataset.columnid != element.kanban_column) {
            const col = document.querySelector(selectors.COLUMNINNER + '[data-id="' + element.kanban_column + '"]');
            col.appendChild(card);
            this.getElement(selectors.ADDCARD, this.id).setAttribute('data-columnid', element.kanban_column);
            card.setAttribute('data-columnid', element.kanban_column);
        }
        const assignees = this.getElement(selectors.ASSIGNEES, this.id);
        const assignedUsers = this.getElements(selectors.ASSIGNEDUSER, this.id);
        const userids = [...assignedUsers].map(v => {
            return v.dataset.userid;
        });
        // Update assignees.
        if (element.assignees !== undefined) {
            const additional = element.assignees.filter(x => !userids.includes(x));
            // Remove all elements that represent users that are no longer assigned to this card.
            if (assignedUsers !== null) {
                assignedUsers.forEach(assignedUser => {
                    if (!element.assignees.includes(assignedUser.dataset.userid)) {
                        assignedUser.parentNode.removeChild(assignedUser);
                    }
                });
            }
            this.toggleClass(element.assignees.length == 0, 'mod_kanban_unassigned');
            // Add new assignees.
            if (element.assignees.length > 0) {
                additional.forEach(async user => {
                    let userdata = this.reactive.state.users.get(user);
                    let data = Object.assign({cardid: element.id}, userdata);
                    data = Object.assign(data, exporter.exportCapabilities(this.reactive.state));
                    Templates.renderForPromise('mod_kanban/user', data).then(({html, js}) => {
                        Templates.appendNodeContents(assignees, html, js);
                        return true;
                    }).catch((error) => displayException(error));
                });
            }
        }
        this.toggleClass(element.selfassigned, 'mod_kanban_selfassigned');
        // Set card completion state.
        if (element.completed !== undefined) {
            this.toggleClass(element.completed == 1, 'mod_kanban_closed');
            if (element.completed == 1) {
                this.getElement(selectors.INPLACEEDITABLE).removeAttribute('data-inplaceeditable');
            } else {
                this.getElement(selectors.INPLACEEDITABLE).setAttribute('data-inplaceeditable', '1');
            }
        }
        // Update title (also in modals).
        if (element.title !== undefined) {
            this.getElement(selectors.INPLACEEDITABLE).setAttribute('data-value', element.title);
            this.getElement(selectors.INPLACEEDITABLE).querySelector('a').innerHTML = element.title;
            this.getElement(selectors.DESCRIPTIONMODALTITLE).innerHTML = element.title;
            this.getElement(selectors.DISCUSSIONMODALTITLE).innerHTML = element.title;
        }
        // Update description.
        if (element.description !== undefined) {
            this.getElement(selectors.DESCRIPTIONMODALBODY).innerHTML = element.description;
        }
        // Render attachments in description modal.
        if (element.attachments !== undefined) {
            Templates.renderForPromise('mod_kanban/attachmentitems', {attachments: element.attachments}).then(({html}) => {
                this.getElement(selectors.DESCRIPTIONMODALFOOTER).innerHTML = html;
                return true;
            }).catch((error) => displayException(error));
        }
        this.toggleClass(element.hasdescription, 'mod_kanban_hasdescription');
        this.toggleClass(element.hasattachment, 'mod_kanban_hasattachment');
        // Update due date.
        if (element.duedate !== undefined) {
            this.getElement(selectors.DUEDATE).setAttribute('data-date', element.duedate);
            this._dueDateFormat();
        }
        this.toggleClass(element.discussion, 'mod_kanban_hasdiscussion');
        // Only option for now is background color.
        if (element.options !== undefined) {
            let options = JSON.parse(element.options);
            if (options.background === undefined) {
                this.getElement().removeAttribute('style');
            } else {
                this.getElement().setAttribute('style', 'background-color: ' + options.background);
            }
        }
        // Enable/disable dragging (e.g. if user is not assigned to the card anymore).
        this.checkDragging();
    }

    /**
     * Delete this card.
     */
    _cardDeleted() {
        this.destroy();
    }

    /**
     * Dispatch event to remove this card.
     * @param {*} event
     */
    _removeCard(event) {
        let target = event.target.closest(selectors.DELETECARD);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('deleteCard', data.id);
    }

    /**
     * Dispatch event to remove this card.
     * @param {*} event
     */
    _removeMessage(event) {
        let target = event.target.closest(selectors.DELETEMESSAGE);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('deleteMessage', data.id);
    }

    /**
     * Dispatch event to complete this card.
     * @param {*} event
     */
    _completeCard(event) {
        let target = event.target.closest(selectors.COMPLETE);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('completeCard', data.id);
    }

    /**
     * Dispatch event to complete this card.
     * @param {*} event
     */
    _uncompleteCard(event) {
        let target = event.target.closest(selectors.UNCOMPLETE);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('uncompleteCard', data.id);
    }

    /**
     * Remove all subcomponents dependencies.
     */
    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    /**
     * Get the draggable data of this component.
     * @returns {object}
     */
    getDraggableData() {
        return {
            id: this.id,
            type: 'card',
        };
    }

    /**
     * Conditionally enable / disable dragging.
     * @param {*} state
     */
    checkDragging(state) {
        if (state === undefined) {
            state = this.reactive.stateManager.state;
        }
        // User may move the card if he/she has moveallcards capability or has moveassignedcards
        // capability and is currently assigned to the card.
        if (state.capabilities.get('moveallcards').value ||
            (state.capabilities.get('moveassignedcards').value &&
            state.cards.get(this.id).assignees.includes(state.common.userid))) {
            this.draggable = true;
            this.dragdrop.setDraggable(true);
        } else {
            this.draggable = false;
            this.dragdrop.setDraggable(false);
        }
    }

    /**
     * Validate draggable data.
     * @param {object} dropdata
     * @returns {boolean} if the data is valid for this drop-zone.
     */
    validateDropData(dropdata) {
        return dropdata?.type == 'card';
    }

    /**
     * Executed when a valid dropdata is dropped over the drop-zone.
     * @param {object} dropdata
     */
    drop(dropdata) {
        if (dropdata.id != this.id) {
            let newcolumn = this.getElement(selectors.ADDCARD, this.id).dataset.columnid;
            let aftercard = this.id;
            this.reactive.dispatch('moveCard', dropdata.id, newcolumn, aftercard);
        }
    }

    /**
     * Dispatch event to unassign the current user.
     * @param {*} event
     */
    _unassignSelf(event) {
        let target = event.target.closest(selectors.UNASSIGNSELF);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('unassignUser', data.id);
    }

    /**
     * Show modal form to edit card details.
     * @param {*} event
     */
    _editDetails(event) {
        event.preventDefault();

        const modalForm = new ModalForm({
            formClass: "mod_kanban\\form\\edit_card_form",
            args: {
                id: this.id,
                boardid: this.boardid,
                cmid: this.cmid,
                groupid: this.groupid,
                userid: this.userid
            },
            modalConfig: {title: getString('editcard', 'mod_kanban')},
            returnFocus: this.getElement(),
        });
        this.addEventListener(modalForm, modalForm.events.FORM_SUBMITTED, this._updateCard);
        modalForm.show();
    }

    /**
     * Dispatch an event to update card data from the detail modal.
     * @param {*} event
     */
    _updateCard(event) {
        this.reactive.dispatch('processUpdates', event.detail);
    }

    /**
     * Update relative time.
     * @param {int} timestamp
     * @returns {string}
     */
    updateRelativeTime(timestamp) {
        let elapsed = new Date(timestamp) - new Date();
        for (var u in this._units) {
            if (Math.abs(elapsed) > this._units[u] || u == 'second') {
                return this.rtf.format(Math.round(elapsed / this._units[u]), u);
            }
        }
        return '';
    }

    /**
     * Format due date using relative time.
     */
    _dueDateFormat() {
        // Convert timestamp to ms.
        let duedate = this.getElement(selectors.DUEDATE).dataset.date * 1000;
        if (duedate > 0) {
            let element = this.getElement(selectors.DUEDATE);
            element.innerHTML = this.updateRelativeTime(duedate);
            if (duedate < new Date().getTime()) {
                element.classList.add('mod_kanban_overdue');
            } else {
                element.classList.remove('mod_kanban_overdue');
            }
        } else {
            this.getElement(selectors.DUEDATE).innerHTML = '';
        }
    }
}
