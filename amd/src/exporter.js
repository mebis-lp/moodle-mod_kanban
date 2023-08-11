import capabilities from 'mod_kanban/capabilities';

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

        let showactionmenu = state.common.userboards == 1 || state.common.groupselector != '' ||
            state.capabilities.get(capabilities.MANAGEBOARD).value ||
            (state.common.userboards == 2 && state.capabilities.get(capabilities.VIEWALLBOARDS).value);

        return Object.assign({
            cmid: state.common.id,
            id: state.board.id,
            sequence: state.board.sequence,
            hascolumns: hascolumns,
            columns: columns,
            locked: state.board.locked,
            hastemplate: state.common.template != 0,
            istemplate: state.board.template != 0,
            heading: state.board.heading,
            groupselector: state.common.groupselector,
            userboards: state.common.userboards,
            history: state.common.history && state.capabilities.get(capabilities.VIEWHISTORY).value,
            groupmode: state.common.groupmode,
            ismyuserboard: state.common.userid == state.board.userid,
            myuserid: state.common.userid,
            showactionmenu: showactionmenu,
            userboardsonly: state.common.userboards == 2,
            iscourseboard: state.board.userid == 0 && state.board.groupid == 0 && state.board.template == 0,
            users: JSON.parse(JSON.stringify(state.users)),
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
        let card = {
            id: cardid,
            title: '-',
            assignees: [],
            options: '{}',
            canedit: false
        };
        if (state.cards.get(cardid) !== undefined) {
            card = JSON.parse(JSON.stringify(state.cards.get(cardid)));
        }
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
        state.discussions.forEach((c) => {
            if (c.kanban_card == cardId) {
                d.push(c);
            }
        });
        d = d.sort((a, b) => parseInt(a.timecreated) > parseInt(b.timecreated));
        return d;
    }

    /**
     * Exports history for a card.
     * @param {*} state
     * @param {number} cardId
     * @returns {array}
     */
    static exportHistory(state, cardId) {
        let d = [];
        // Only get history of this card.
        state.history.forEach((c) => {
            if (c.kanban_card == cardId) {
                d.push(c);
            }
        });
        // Sort by timestamp.
        d = d.sort((a, b) => parseInt(a.timestamp) > parseInt(b.timestamp));
        return d;
    }
}