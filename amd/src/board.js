import {DragDrop} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
import exporter from 'mod_kanban/exporter';
import KanbanComponent from 'mod_kanban/kanbancomponent';
import {saveCancel} from 'core/notification';
import {get_string as getString} from 'core/str';

/**
 * Component representing a kanban board.
 */
export default class extends KanbanComponent {
    LOCKED_COLUMNS = 1;
    LOCKED_COMPLETE = 2;

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
        this.addEventListener(
            this.getElement(selectors.LOCKBOARDCOLUMNS),
            'click',
            this._lockColumns
        );
        this.addEventListener(
            this.getElement(selectors.UNLOCKBOARDCOLUMNS),
            'click',
            this._unlockColumns
        );
        this.addEventListener(
            this.getElement(selectors.SAVEASTEMPLATE),
            'click',
            this._templateConfirm
        );
        this.dragdrop = new DragDrop(this);
        this._continuousUpdate();
    }

    _continuousUpdate() {
        setInterval(() => {
            this.reactive.dispatch('getUpdates');
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

    /**
     * Display confirmation modal for saving a board as template.
     */
    _templateConfirm() {
        saveCancel(
            getString('saveastemplate', 'mod_kanban'),
            getString('saveastemplateconfirm', 'mod_kanban'),
            getString('save', 'core'),
            () => {
                this._saveAsTemplate();
            }
        );
    }

    _saveAsTemplate() {
        this.reactive.dispatch('saveAsTemplate');
    }

    _boardUpdated({element}) {
        const colcontainer = this.getElement(selectors.COLUMNCONTAINER);
        if (element.sequence !== undefined) {
            let sequence = element.sequence.split(',');
            [...colcontainer.children]
            .forEach((node) => {
                if (node.classList.contains('mod_kanban_column') && !sequence.includes(node.dataset.id)) {
                    colcontainer.removeChild(node);
                }
            });
            [...colcontainer.children]
            .sort((a, b)=>sequence.indexOf(a.dataset.id) > sequence.indexOf(b.dataset.id) ? 1 : -1)
            .forEach(node=>colcontainer.appendChild(node));
        }
        this.toggleClass(element.locked, 'mod_kanban_board_locked_columns');
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
        this.getElement(selectors.COLUMNCONTAINER).appendChild(placeholder);
        const newcomponent = await this.renderComponent(placeholder, 'mod_kanban/column', data);
        const newelement = newcomponent.getElement();
        this.getElement(selectors.COLUMNCONTAINER).replaceChild(newelement, placeholder);
        if (element.highlight !== undefined && element.highlight) {
            newelement.classList.add('mod_kanban_updated');
            setTimeout(() => this.newelement.classList.remove('mod_kanban_updated'), 3000);
        }
    }

    _addColumn() {
        this.reactive.dispatch('addColumn', 0);
    }

    _lockColumns() {
        this.reactive.dispatch('lockColumns');
    }

    _unlockColumns() {
        this.reactive.dispatch('unlockColumns');
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