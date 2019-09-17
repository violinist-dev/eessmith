import React from 'react'

/**
 * The tab <li> contents.
 */
export default class TabButtonLabelComponent extends React.Component {
    render () {
        return (
            <>
                <strong className="menu-item-title">{this.props.label}</strong>
                <strong className="menu-item-summary">({this.props.count}/{this.props.total})</strong>
            </>
        )
    }
}
