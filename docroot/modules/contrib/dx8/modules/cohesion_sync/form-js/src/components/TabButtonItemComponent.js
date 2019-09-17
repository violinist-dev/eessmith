import React from 'react'

/**
 * The <li> item in the vertical tabs list.
 */
export default class TabButtonItemComponent extends React.Component {
    render () {
        // Set the selected class.
        const classes = `menu-item ${this.props.isSelected ? 'is-selected' : ''}`

        // Render the elements.
        return (
            <li className={classes} tabIndex={this.key}>
                <a href={this.props.href} onClick={this.props.onClick}>
                    {this.props.renderItem}
                </a>
            </li>
        )
    }
}
