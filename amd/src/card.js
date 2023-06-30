import {BaseComponent, DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';
import {saveCancel} from 'core/notification';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

/**
 * Component representing a card in a kanban board.
 */
export default class extends BaseComponent {
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
        ];
    }

    /**
     * Called once when state is ready, attaching event listeners and initializing drag and drop.
     * @param {*} state The initial state
     */
    stateReady(state) {
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
        this.draggable = false;
        this.dragdrop = new DragDrop(this);
        this.checkDragging(state);
        this.boardid = state.board.id;
        this.cmid = state.board.cmid;
    }

    /**
     * Display confirmation modal for deleting a card.
     * @param {*} event
     */
    _removeConfirm(event) {
        saveCancel(
            getString('deletecard', 'mod_kanban'),
            getString('deletecardconfirm', 'mod_kanban'),
            getString('delete', 'core'),
            () => {
                this._removeCard(event);
            }
        );
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
        let target = event.target.closest(selectors.ADDCARD);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('addCard', data.columnid, data.id);
    }

    /**
     * Update this card.
     * @param {*} param0
     */
    async _cardUpdated({element}) {
        const card = this.getElement();
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
        const additional = element.assignees.filter(x => !userids.includes(x));
        if (assignedUsers !== null) {
            assignedUsers.forEach(assignedUser => {
                if (!element.assignees.includes(assignedUser.dataset.userid)) {
                    assignedUser.parentNode.removeChild(assignedUser);
                }
            });
        }
        if (element.assignees.length > 0) {
            additional.forEach(async user => {
                let placeholder = document.createElement('div');
                let userdata = this.reactive.state.users.get(user);
                let data = Object.assign({cardid: element.id}, userdata);
                data = Object.assign(data, exporter.exportCapabilities(this.reactive.state));
                placeholder.setAttribute('data-id', element.id);
                assignees.appendChild(placeholder);
                const newcomponent = await this.renderComponent(placeholder, 'mod_kanban/user', data);
                const newelement = newcomponent.getElement();
                assignees.replaceChild(newelement, placeholder);
            });
        }
        if (element.selfassigned !== undefined) {
            if (element.selfassigned) {
                this.getElement(selectors.ASSIGNSELF, this.id).parentNode.classList.add('hidden');
                this.getElement(selectors.UNASSIGNSELF, this.id).parentNode.classList.remove('hidden');
            } else {
                this.getElement(selectors.ASSIGNSELF, this.id).parentNode.classList.remove('hidden');
                this.getElement(selectors.UNASSIGNSELF, this.id).parentNode.classList.add('hidden');
            }
        }
        if (element.completed !== undefined) {
            if (element.completed) {
                this.getElement(selectors.COMPLETIONSTATE).classList.remove('hidden');
                this.getElement(selectors.UNCOMPLETE).parentNode.classList.remove('hidden');
                this.getElement(selectors.COMPLETE).parentNode.classList.add('hidden');
                this.getElement(selectors.INPLACEEDITABLE).removeAttribute('data-inplaceeditable');
            } else {
                this.getElement(selectors.COMPLETIONSTATE).classList.add('hidden');
                this.getElement(selectors.UNCOMPLETE).parentNode.classList.add('hidden');
                this.getElement(selectors.COMPLETE).parentNode.classList.remove('hidden');
                this.getElement(selectors.INPLACEEDITABLE).setAttribute('data-inplaceeditable', '1');
            }
        }
        if (element.title !== undefined) {
            this.getElement(selectors.INPLACEEDITABLE).setAttribute('data-value', element.title);
            this.getElement(selectors.INPLACEEDITABLE).querySelector('a').innerHTML = element.title;
            this.getElement(selectors.DESCRIPTIONMODALTITLE).innerHTML = element.title;
        }
        if (element.description !== undefined) {
            this.getElement(selectors.DESCRIPTIONMODALBODY).innerHTML = element.description;
        }
        if (element.hasdescription !== undefined) {
            if (element.hasdescription) {
                this.getElement(selectors.DESCRIPTIONTOGGLE).classList.remove('hidden');
            } else {
                this.getElement(selectors.DESCRIPTIONTOGGLE).classList.add('hidden');
            }

        }
        this.checkDragging();
    }

    /**
     * Delete this card.
     */
    _cardDeleted() {
        const el = this.getElement();
        el.parentNode.removeChild(el);
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
        if (state.capabilities.get('moveallcards').value ||
            (state.capabilities.get('moveassignedcards').value &&
            state.cards.get(this.id).assignees.includes(state.board.userid))) {
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
                kanban_board: this.boardid,
                cmid: this.cmid
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
}
