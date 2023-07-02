import {BaseComponent, DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';
/**
 * Component representing a kanban board.
 */
export default class extends BaseComponent {
    static init(target) {
        let element = document.getElementById(target);
        return new this({
            element: element,
        });
    }

    create() {
        this.cmid = this.element.dataset.cmid;
        this.id = this.element.dataset.id;
    }

    getWatchers() {
        return [
            {watch: `board:updated`, handler: this._boardUpdated},
            {watch: `columns:created`, handler: this._createColumn},
        ];
    }

    async stateReady() {
        this.addEventListener(
            this.getElement(selectors.ADDCOLUMNFIRST),
            'click',
            this._addColumn
        );
        this.dragdrop = new DragDrop(this);
        this._continuousUpdate();
    }

    _continuousUpdate() {
        setTimeout(() => {
            this.reactive.dispatch('getUpdates');
            this._continuousUpdate();
        }, 10000);
    }

    /**
     * Remove all subcomponents dependencies.
     */
    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    _boardUpdated({element}) {
        const el = this.getElement();
        let sequence = element.sequence.split(',');
        [...el.children]
        .sort((a, b)=>sequence.indexOf(a.dataset.id) > sequence.indexOf(b.dataset.id) ? 1 : -1)
        .forEach(node=>el.appendChild(node));
    }

    async _createColumn({element}) {
        let data = Object.assign({
            id: element.id,
            title: element.title,
            options: element.options,
            sequence: element.sequence,
        }, exporter.exportCapabilities(this.reactive.state));
        let placeholder = document.createElement('li');
        placeholder.setAttribute('data-id', data.id);
        this.getElement().appendChild(placeholder);
        const newcomponent = await this.renderComponent(placeholder, 'mod_kanban/column', data);
        const newelement = newcomponent.getElement();
        this.getElement().replaceChild(newelement, placeholder);
    }

    _addColumn() {
        this.reactive.dispatch('addColumn', 0);
    }

    /**
     * Validate draggable data.
     * @param {object} dropdata
     * @returns {boolean} if the data is valid for this drop-zone.
     */
    validateDropData(dropdata) {
        let type = dropdata?.type;
        return type == 'column';
    }

    /**
     * Executed when a valid dropdata is dropped over the drop-zone.
     * @param {object} dropdata
     */
    drop(dropdata) {
        this.reactive.dispatch('moveColumn', dropdata.id, 0);
    }

    /**
     * Optional method to show some visual hints to the user.
     */
    showDropZone() {
        this.getElement(selectors.ADDCOLUMNCONTAINER).classList.add('mod_kanban_insert');
    }

    /**
     * Optional method to remove visual hints to the user.
     */
    hideDropZone() {
        this.getElement(selectors.ADDCOLUMNCONTAINER).classList.remove('mod_kanban_insert');
    }
}