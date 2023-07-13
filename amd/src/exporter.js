/**
 * Exporter for use in mustache template.
 */
export default class {
    /**
     * Exports the complete state (for initial rendering).
     * @param {*} state
     * @returns {object}
     */
    static exportStateForTemplate(state) {
        let columnOrder = state.board.sequence.split(',');
        let columns = [];
        let hascolumns = state.board.sequence != '';
        if (hascolumns) {
            columns = columnOrder.map((value) => {
                return this.exportCardsForColumn(state, value);
            });
        }
        return Object.assign({
            cmid: state.common.id,
            id: state.board.id,
            sequence: state.board.sequence,
            hascolumns: hascolumns,
            columns: columns,
            locked: state.board.locked
        }, this.exportCapabilities(state));
    }
    /**
     * Exports the card for one column.
     * @param {*} state
     * @param {*} columnid
     * @returns {object}
     */
    static exportCardsForColumn(state, columnid) {
        let col = JSON.parse(JSON.stringify(state.columns.get(columnid)));
        let options = JSON.parse(col.options);
        col.hascards = col.sequence != '';
        col.autoclose = options.autoclose;
        col.autohide = options.autohide;
        if (col.hascards) {
            let cardOrder = col.sequence.split(',');
            col.cards = cardOrder.map((value) => {
                return this.exportCard(state, value);
            });
        }
        return col;
    }
    /**
     * Exports a card.
     * @param {*} state
     * @param {*} cardid
     * @returns {object}
     */
    static exportCard(state, cardid) {
        let card = JSON.parse(JSON.stringify(state.cards.get(cardid)));
        card.cardid = card.id;
        card.hasassignees = card.assignees.length > 0;
        let options = JSON.parse(card.options);
        if (card.hasassignees && typeof card.assignees[0] == 'number') {
            card.assignees = card.assignees.map((userid) => {
                return state.users.get(userid);
            });
            card.assignees = [...new Set(card.assignees)];
        }
        return Object.assign(card, options);
    }

    /**
     * Exports the capabilities.
     * @param {*} state
     * @returns {object}
     */
    static exportCapabilities(state) {
        let capabilities = [];
        state.capabilities.forEach((c) => {
            capabilities[c.id] = c.value;
        });
        return Object.assign({}, capabilities);
    }

    /**
     * Exports the discussion for a card.
     * @param {*} state
     * @param {number} cardId
     * @returns {array}
     */
    static exportDiscussion(state, cardId) {
        let d = [];
        state.discussions.get(cardId).values.forEach((c) => {
            if (c === null) {
                return;
            }
            d.push(c);
        });
        d = d.sort((a, b) => parseInt(a.timecreated) > parseInt(b.timecreated));
        return d;
    }
}