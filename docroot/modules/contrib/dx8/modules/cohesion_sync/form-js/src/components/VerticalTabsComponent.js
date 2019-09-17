import React from 'react'

/**
 * The wrapper for the vertical tabs widget.
 */
export default class VerticalTabsComponent extends React.Component {
    render () {
        return (
            <div className="package-tabs clearfix">
                <ul className="tabs__menu">
                    {this.props.buttonList}
                </ul>
                <div className="tabs__panes">
                    {this.props.children}
                </div>
            </div>
        )
    }
}
