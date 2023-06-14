import {BaseComponent, DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';

/**
 * Component representing a column in a kanban board.
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
     * @returns {array}
     */
    getWatchers() {
        return [
            {watch: `columns[${this.id}]:updated`, handler: this._columnUpdated},
            {watch: `columns[${this.id}]:deleted`, handler: this._columnDeleted},
            {watch: `cards:created`, handler: this._cardCreated}
        ];
    }

    /**
     * Called once when state is ready, attaching event listeners and initializing drag and drop.
     */
    stateReady() {
        this.addEventListener(
            this.getElement(selectors.DELETECOLUMN, this.id),
            'click',
            this._removeColumn
        );
        this.addEventListener(
            this.getElement(selectors.ADDCARDFIRST),
            'click',
            this._addCard
        );
        this.addEventListener(
            this.getElement(selectors.ADDCOLUMN, this.id),
            'click',
            this._addColumn
        );
        this.dragdrop = new DragDrop(this);
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
     *
     * @returns {Object} the draggable data.
     */
    getDraggableData() {
        return {id: this.id, type: 'column'};
    }

    /**
     * Validate draggable data.
     * @param {object} dropdata
     * @returns {boolean} if the data is valid for this drop-zone.
     */
    validateDropData(dropdata) {
        let type = dropdata?.type;
        return type == 'card' || type == 'column';
    }

    /**
     * Executed when a valid dropdata is dropped over the drop-zone.
     * @param {object} dropdata
     * @param {object} event
     */
    drop(dropdata, event) {
        if (dropdata.type == 'card') {
            let cards = this.getElements(selectors.CARD);
            let aftercard = 0;
            for (let i = 0; i < cards.length; i++) {
                if (cards[i].offsetTop + cards[i].clientHeight / 2 <= event.layerY) {
                    aftercard = cards[i].dataset.id;
                }
            }
            this.reactive.dispatch('moveCard', dropdata.id, this.id, aftercard);
        }
        if (dropdata.type == 'column') {
            if (dropdata.id != this.id) {
                this.reactive.dispatch('moveColumn', dropdata.id, this.id);
            }
        }
    }

    /**
     * Optional method to show some visual hints to the user.
     * @param {object} dropdata
     * @param {object} event
     */
    showDropZone(dropdata, event) {
        if (dropdata.type == 'card') {
            let cards = this.getElements(selectors.CARD);
            let aftercard = 0;
            for (let i = 0; i < cards.length; i++) {
                if (cards[i].offsetTop + cards[i].clientHeight / 2 <= event.layerY) {
                    aftercard = cards[i].dataset.id;
                }
            }
            if (aftercard == 0) {
                this.getElement(selectors.ADDCARDCONTAINER).classList.add('mod_kanban_insert');
            } else {
                this.getElement(selectors.ADDCARDCONTAINER, aftercard).classList.add('mod_kanban_insert');
            }
        }
        if (dropdata.type == 'column') {
            this.getElement(selectors.ADDCOLUMNCONTAINER).classList.add('mod_kanban_insert');
        }
    }

    /**
     * Optional method to remove visual hints to the user.
     */
    hideDropZone() {
        this.getElement(selectors.ADDCOLUMNCONTAINER).classList.remove('mod_kanban_insert');
        this.getElements(selectors.ADDCARDCONTAINER).forEach((e) => {
            e.classList.remove('mod_kanban_insert');
        });
    }

    /**
     * Dispatch event to add a column after this column.
     * @param {*} event
     */
    _addColumn(event) {
        let target = event.target.closest(selectors.ADDCOLUMN);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('addColumn', data.id);
    }

    /**
     * Called when a card was created in this column.
     * @param {*} param0
     */
    async _cardCreated({element}) {
        if (element.kanban_column == this.id) {
            let data = Object.assign({
                id: element.id,
                title: element.title,
                options: element.options,
                // eslint-disable-next-line
                kanban_column: element.kanban_column,
            }, exporter.exportCapabilities(this.reactive.state));
            let placeholder = document.createElement('li');
            placeholder.setAttribute('data-id', data.id);
            let node = this.getElement(selectors.COLUMNINNER, this.id);
            node.appendChild(placeholder);
            const newcomponent = await this.renderComponent(placeholder, 'mod_kanban/card', data);
            const newelement = newcomponent.getElement();
            node.replaceChild(newelement, placeholder);
        }
    }

    /**
     * Dispatch event to add a card in this column.
     * @param {*} event
     */
    _addCard(event) {
        let target = event.target.closest(selectors.ADDCARD);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('addCard', data.columnid, 0);
    }

    /**
     * Called when column is updated.
     * @param {*} param0
     */
    _columnUpdated({element}) {
        const el = this.getElement(selectors.COLUMNINNER, this.id);
        let sequence = element.sequence.split(',');
        [...el.children]
        .sort((a, b)=>sequence.indexOf(a.dataset.id) > sequence.indexOf(b.dataset.id) ? 1 : -1)
        .forEach(node=>el.appendChild(node));
    }

    /**
     * Called when this column is deleted.
     */
    _columnDeleted() {
        const el = this.getElement();
        el.parentNode.removeChild(el);
        this.destroy();
    }

    /**
     * Dispatch event to remove this column.
     * @param {*} event
     */
    _removeColumn(event) {
        let target = event.target.closest('[data-action="delete_column"]');
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('deleteColumn', data.id);
    }
}