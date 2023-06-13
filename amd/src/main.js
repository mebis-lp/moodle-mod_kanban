import Reactive from 'mod_kanban/reactive';
import KanbanComponent from 'mod_kanban/kanban';
import KanbanMutations from 'mod_kanban/mutations';

/**
 * Create reactive instance for kanban, load initial state.
 * @param {string} domElementId Id of render container
 * @param {number} cmId Course module id of the kanban board
 * @param {number} boardId Id of the board to display
 * @returns {KanbanComponent}
 */
export const init = (domElementId, cmId, boardId) => {
    const reactiveInstance = new Reactive({
        name: 'kanban_' + cmId,
        eventName: stateChangedEventName,
        eventDispatch: dispatchKanbanEvent,
        target: document.getElementById(domElementId),
        mutations: new KanbanMutations(),
    });
    reactiveInstance.loadBoard(cmId, boardId);
    return new KanbanComponent({
        element: document.getElementById(domElementId),
        reactive: reactiveInstance,
    });
};

const stateChangedEventName = 'mod_kanban:stateChanged';

/**
 * Internal state changed event.
 *
 * @method dispatchKanbanEvent
 * @param {object} detail the full state
 * @param {object} target the custom event target (document if none provided)
 */
function dispatchKanbanEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(
        new CustomEvent(
            stateChangedEventName,
            {
                bubbles: true,
                detail: detail,
            }
        )
    );
}