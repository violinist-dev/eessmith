import React from 'react'

import { TableSelectContainer } from '../containers'
import { DetailsComponent } from '../components'

/**
 * Details section.
 */
export default class DetailsContainer extends React.Component {
    render () {
        let total = 0
        let count = 0
        let groupTables = []
        Object.keys(this.props.data.groups).forEach((key) => {
            // Get the counts.
            Object.keys(this.props.data.groups[key].items).forEach((key) => {
                if (this.props.uuidStatusCallback(key)) {
                    count += 1
                }
                total += 1
            })

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
            <DetailsComponent label={`${this.props.data.label} (${count}/${total})`}>
                {groupTables}
            </DetailsComponent>
        )
    }
}
