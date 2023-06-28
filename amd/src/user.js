import {BaseComponent} from 'core/reactive';

/**
 * Component representing an assigned user. Kept as a stub for future use.
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
        this.userid = this.element.dataset.userid;
    }
    /**
     * Watchers for this component.
     * @returns {array} All watchers for this component
     */
    getWatchers() {
        return [
            {watch: `cards[${this.id}]:deleted`, handler: this._cardDeleted},
        ];
    }
    /**
     * Called once when state is ready, attaching event listeners.
     */
    stateReady() {
        // This function does nothing for now but needs to exist.
    }

    /**
     * Called when card is deleted.
     */
    _cardDeleted() {
        this.destroy();
    }
}