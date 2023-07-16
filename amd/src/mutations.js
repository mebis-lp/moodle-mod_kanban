import Ajax from 'core/ajax';
/**
 * Mutations library for mod_kanban.
 * The functions are just used to forward data to the webservice.
 */
export default class {
    init(stateManager) {
        stateManager.addUpdateTypes({
            discussionput: this._discussionPut,
            discussiondelete: this._discussionDelete,
        });
    }

    _discussionPut(stateManager, name, fields) {
        stateManager.setReadOnly(false);
        const cardid = parseInt(fields.kanban_card);
        if (stateManager.state.discussions.get(cardid) === undefined) {
            stateManager.state.discussions.set(cardid, {'id': cardid, values: []});
        }
        stateManager.state.discussions.get(cardid).values[fields.id] = fields;
        stateManager.eventsToPublish.push({
            eventName: `${name}:updated`,
            eventData: fields,
            action: `updated`,
        });
        stateManager.setReadOnly(false);
    }

    _discussionDelete(stateManager, name, fields) {
        stateManager.setReadOnly(false);
        const cardid = parseInt(fields.kanban_card);
        if (stateManager.state.discussions.get(cardid) !== undefined) {
            delete stateManager.state.discussions.get(cardid).values[fields.id];
            stateManager.eventsToPublish.push({
                eventName: `${name}:updated`,
                eventData: fields,
                action: `updated`,
            });
        }
        stateManager.setReadOnly(false);
    }

    async saveAsTemplate(stateManager) {
        await this.sendChange('save_as_template', stateManager);
    }

    /**
     * Delete a card.
     * @param {*} stateManager StateManager instance
     * @param {number} cardId Id of the card to be deleted
     */
    async deleteCard(stateManager, cardId) {
        await this.sendChange('delete_card', stateManager, {cardid: cardId});
    }

    /**
     * Add a card after an existing one.
     * @param {*} stateManager StateManager instance
     * @param {number} columnId Id of the column
     * @param {number} afterCard Id of the card before (0 means to insert at the top of the column)
     */
    async addCard(stateManager, columnId, afterCard) {
        await this.sendChange('add_card', stateManager, {columnid: columnId, aftercard: afterCard});
    }

    /**
     * Move a card to another column.
     * @param {*} stateManager StateManager instance
     * @param {number} cardId Id of the card to be deleted
     * @param {number} columnId Id of the new column
     * @param {number} afterCard Id of the card before (0 means to move at the top of the column)
     */
    async moveCard(stateManager, cardId, columnId, afterCard) {
        await this.sendChange('move_card', stateManager, {cardid: cardId, columnid: columnId, aftercard: afterCard});
    }

    /**
     * Deletes a column and all cards within.
     * @param {*} stateManager StateManager instance
     * @param {number} columnId Id of the column to delete
     */
    async deleteColumn(stateManager, columnId) {
        await this.sendChange('delete_column', stateManager, {columnid: columnId});
    }

    /**
     * Adds a new column.
     * @param {*} stateManager StateManager instance
     * @param {number} afterColumn Id of the column before (0 means to insert at the left of the board)
     */
    async addColumn(stateManager, afterColumn) {
        await this.sendChange('add_column', stateManager, {aftercol: afterColumn});
    }

    /**
     * Moves a column to a new place.
     * @param {*} stateManager StateManager instance
     * @param {number} columnId Id of the column to move
     * @param {number} afterColumn Id of the column before (0 means to insert at the left of the board)
     */
    async moveColumn(stateManager, columnId, afterColumn) {
        await this.sendChange('move_column', stateManager, {columnid: columnId, aftercol: afterColumn});
    }

    /**
     * Assign a user to a card.
     * @param {*} stateManager StateManager instance.
     * @param {number} cardId Id of the card
     * @param {number} userId Id of the user to assign (0 means to assign the current user)
     */
    async assignUser(stateManager, cardId, userId = 0) {
        await this.sendChange('assign_user', stateManager, {cardid: cardId, userid: userId});
    }

    /**
     * Mark a card as completed.
     * @param {*} stateManager StateManager instance.
     * @param {number} cardId Id of the card
     */
    async completeCard(stateManager, cardId) {
        await this.sendChange('set_card_complete', stateManager, {cardid: cardId, state: 1});
    }

    /**
     * Mark a card as not completed.
     * @param {*} stateManager StateManager instance.
     * @param {number} cardId Id of the card
     */
    async uncompleteCard(stateManager, cardId) {
        await this.sendChange('set_card_complete', stateManager, {cardid: cardId, state: 0});
    }

    /**
     * Remove assignment for a user to a card.
     * @param {*} stateManager StateManager instance.
     * @param {number} cardId Id of the card
     * @param {number} userId Id of the user to unassign, defaults to 0 (current user)
     */
    async unassignUser(stateManager, cardId, userId = 0) {
        await this.sendChange('unassign_user', stateManager, {cardid: cardId, userid: userId});
    }

    /**
     * Locks a column.
     * @param {*} stateManager StateManager instance
     * @param {number} columnId Id of the column to lock
     */
    async lockColumn(stateManager, columnId) {
        await this.sendChange('set_column_locked', stateManager, {columnid: columnId, state: 1});
    }

    /**
     * Unlocks a column.
     * @param {*} stateManager StateManager instance
     * @param {number} columnId Id of the column to unlock
     */
    async unlockColumn(stateManager, columnId) {
        await this.sendChange('set_column_locked', stateManager, {columnid: columnId, state: 0});
    }

    /**
     * Locks all columns of the board.
     * @param {*} stateManager StateManager instance
     */
    async lockColumns(stateManager) {
        await this.sendChange('set_board_columns_locked', stateManager, {state: 1});
    }

    /**
     * Unlocks all columns of the board.
     * @param {*} stateManager StateManager instance
     */
    async unlockColumns(stateManager) {
        await this.sendChange('set_board_columns_locked', stateManager, {state: 0});
    }

    /**
     * Adds a message to discussion.
     * @param {*} stateManager
     * @param {*} cardId
     * @param {*} message
     */
    async sendDiscussionMessage(stateManager, cardId, message) {
        await this.sendChange('add_discussion_message', stateManager, {cardid: cardId, message: message});
    }

    /**
     * Delete a message from a discussion.
     * @param {*} stateManager StateManager instance
     * @param {number} messageId Id of the message to be deleted
     */
    async deleteMessage(stateManager, messageId) {
        await this.sendChange('delete_discussion_message', stateManager, {messageid: messageId});
    }

    /**
     * Send change request to webservice
     * @param {string} method Name of the method
     * @param {*} stateManager StateManager instance
     * @param {object} data Data to send
     */
    async sendChange(method, stateManager, data) {
        const state = stateManager.state;
        const result = await Ajax.call([{
            methodname: 'mod_kanban_change_kanban_content_' + method,
            args: {
                cmid: state.common.id,
                boardid: state.board.id,
                data: data
            },
        }])[0];

        this.processUpdates(stateManager, result);
    }

    /**
     * Update state.
     * @param {*} stateManager
     */
    async getUpdates(stateManager) {
        const state = stateManager.state;
        const result = await Ajax.call([{
            methodname: 'mod_kanban_get_kanban_content_update',
            args: {
                cmid: state.common.id,
                boardid: state.board.id,
                timestamp: state.common.timestamp,
            },
        }])[0];

        this.processUpdates(stateManager, result);
    }

    /**
     * Update discussions for a card.
     * @param {*} stateManager
     * @param {number} cardId
     */
    async getDiscussionUpdates(stateManager, cardId) {
        const state = stateManager.state;
        let timestamp = 0;
        if (state.discussions.get(cardId) !== undefined) {
            state.discussions.get(cardId).values.forEach((discussion) => {
                if (discussion.timestamp === undefined) {
                    return;
                }
                if (discussion.timestamp > timestamp) {
                    timestamp = discussion.timestamp;
                }
            });
        }
        const result = await Ajax.call([{
            methodname: 'mod_kanban_get_discussion_update',
            args: {
                cmid: state.common.id,
                boardid: state.board.id,
                cardid: cardId,
                timestamp: timestamp,
            },
        }])[0];

        this.processUpdates(stateManager, result);
    }

    /**
     * Process updates.
     *
     * @param {*} stateManager
     * @param {*} result
     */
    async processUpdates(stateManager, result) {
        let updates = JSON.parse(result.update);
        stateManager.processUpdates(updates);
    }
}