import React from 'react'

import { TabButtonLabelComponent } from '../components'

/**
 * A single lhs tab button.
 */
export default class TabButtonLabelContainer extends React.Component {
    /**
     * Render the component.
     * @returns {*}
     */
    render () {
        // Give the array of items in the form, calculate tht total and number checked.
        let total = 0
        let count = 0
        Object.keys(this.props.data.groups).forEach((key) => {
            // Get the counts.
            Object.keys(this.props.data.groups[key].items).forEach((key) => {
                if (this.props.uuidStatusCallback(key)) {
                    count += 1
                }
                total += 1
            })
        })

        // Render the labe with the count and total.
        return (
            <TabButtonLabelComponent
                label={this.props.data.label}
                count={count}
                total={total}
                />
        )
    }
}
