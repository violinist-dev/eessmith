import React from 'react'

import { TableSelectComponent, TableSelectRowComponent } from '../components'

/**
 * Table select group within a details section.
 */
export default class TableSelectContainer extends React.Component {
    /**
     * Set up a state for the "check all" checkbox.
     */
    constructor () {
        super()
        this.state = {
            allChecked: false
        }
    }

    /**
     * Calculate if the "check all" checkbox should be set.
     */
    defaultCheckedAll() {
        // Is at least one item in the list unchecked?
        let allChecked = Object.keys(this.props.data.items).length ? true : false

        Object.keys(this.props.data.items).forEach((key) => {
            if (this.props.uuidStatusCallback(key) === false) {
                allChecked = false
            }
        })

        // Set the status.
        this.setState({
            allChecked
        });
    }

    /**
     * User clicked to toggle all checkboxes on/off.
     */
    toggleAll () {
        // Toggle the state.
        let newState = !this.state.allChecked

        // Loop through al the callbacks and set them to this state.
        Object.keys(this.props.data.items).forEach((key) => {
            // If the current state doesn't match the desired state, then change it.
            if (this.props.uuidStatusCallback(key) !== newState) {
                this.props.uuidChangeCallback(key, this.props.data.items[key].type)
            }
        });

        // Save the state and re-render.
        this.setState({
            allChecked: newState
        });
    }

    /**
     * Toggle a single checkbox by running the callback and re-calculating the "check all" status.
     * @param key
     * @param type
     */
    toggle (key, type) {
        // Run the callback.
        this.props.uuidChangeCallback(key, type)

        // Re-render the taggle all checkbox.
        this.defaultCheckedAll()
    }

    /**
     * Initially calculate the status of the "check all" checkbox.
     * @returns {Promise<void>}
     */
    async componentDidMount () {
        this.defaultCheckedAll()
    }

    /**
     * Render the component.
     * @returns {*}
     */
    render () {
        // Render the rows.
        let rows = []
        Object.keys(this.props.data.items).forEach((key) => {
            // Render the row.
            rows.push(
                <TableSelectRowComponent
                    key={key}
                    inputName={key}
                    label={this.props.data.items[key].label}
                    defaultChecked={() => this.props.uuidStatusCallback(key)}
                    onClick={() => {this.toggle(key, this.props.data.items[key].type)}}
                />
            )
        })

        if (rows.length) {
            // If there was some content, render it.
            return (
                <TableSelectComponent
                    label={this.props.data.label}
                    defaultChecked={this.state.allChecked}
                    onClick={() => this.toggleAll()}
                >
                    {rows}
                </TableSelectComponent>
            )
        } else {
            // No content, so don't try and render the table select at all.
            return (
                <div/>
            )
        }
    }
}