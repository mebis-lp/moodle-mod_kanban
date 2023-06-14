import {BaseComponent, DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';

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
            this._removeCard
        );
        this.addEventListener(
            this.getElement(selectors.ADDCARD, this.id),
            'click',
            this._addCard
        );
        this.addEventListener(
            this.getElement(selectors.ASSIGNUSER, this.id),
            'click',
            this._assignUser
        );
        if (state.cards.get(this.id).assignees.length > 0) {
            this.getElement(selectors.ASSIGNUSER, this.id).classList.add('mod_kanban_hidden');
        }
        this.draggable = false;
        this.dragdrop = new DragDrop(this);
        this.checkDragging(state);
    }

    /**
     * Dispatch event to assign a user to the card.
     * @param {*} event
     */
    _assignUser(event) {
        let target = event.target.closest(selectors.ASSIGNUSER);
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
                placeholder.setAttribute('data-action', 'unassign_user');
                assignees.appendChild(placeholder);
                const newcomponent = await this.renderComponent(placeholder, 'mod_kanban/user', data);
                const newelement = newcomponent.getElement();
                assignees.replaceChild(newelement, placeholder);
                assignees.appendChild(this.getElement(selectors.ASSIGNUSER, this.id));
            });
            this.getElement(selectors.ASSIGNUSER, this.id).classList.add('mod_kanban_hidden');
        } else {
            this.getElement(selectors.ASSIGNUSER, this.id).classList.remove('mod_kanban_hidden');
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
        let target = event.target.closest('[data-action="delete_card"]');
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('deleteCard', data.id);
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
}
