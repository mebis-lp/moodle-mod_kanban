import {BaseComponent} from 'core/reactive';
import selectors from 'mod_kanban/selectors';
/**
 * Component representing an assigned user.
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
     * @param {*} state
     */
    stateReady(state) {
        if ((state.capabilities.assignself && this.userid == state.board.userid) || state.capabilities.assignothers) {
            this.addEventListener(
                this.getElement(),
                'click',
                this._unassignUser
            );
        }
    }
    /**
     * Dispatch event to unassign a user.
     * @param {*} event
     */
    _unassignUser(event) {
        let target = event.target.closest(selectors.UNASSIGNUSER);
        let data = Object.assign({}, target.dataset);
        this.reactive.dispatch('unassignUser', data.id, data.userid);
    }
    /**
     * Called when card is deleted.
     */
    _cardDeleted() {
        this.destroy();
    }
}