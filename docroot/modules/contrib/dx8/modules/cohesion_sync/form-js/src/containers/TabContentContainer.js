import React from 'react'

import { TableSelectContainer } from '../containers'
import { DetailsComponent } from '../components'

/**
 * The contents of the tabs.
 */
export default class TabContentContainer extends React.Component {
    /**
     * Render the component.
     * @returns {*}
     */
    render () {
        let groupTables = []
        Object.keys(this.props.data.groups).forEach((key) => {
            // Render the accordion.
            groupTables.push(
                <TableSelectContainer
                    key={key}
                    dataKey={key}
                    data={this.props.data.groups[key]}
                    uuidStatusCallback={this.props.uuidStatusCallback}
                    uuidChangeCallback={this.props.uuidChangeCallback}
                />
            )
        })

        // Render tables.
        if (groupTables === null) {
            groupTables = []
        }

        return (
            <>{groupTables.length ? groupTables : <div>No entities available.</div>}</>
        )
    }
}
