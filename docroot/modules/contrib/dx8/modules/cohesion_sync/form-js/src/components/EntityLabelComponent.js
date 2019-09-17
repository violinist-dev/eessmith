import React from 'react'

/**
 * Simple label display.
 */
export default class EntityLabelComponent extends React.Component {
    render () {
        return (
            <p><strong>{this.props.label}</strong></p>
        )
    }
}
