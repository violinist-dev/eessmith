import React from 'react'

/**
 * Re-usable <button> element that can receive an onclick callback.
 */
export default class ApplyButtonComponent extends React.Component {
    render () {
        return (
            <div className="apply-wrapper">
                <button
                    data-action={this.props.dataAction}
                    type="button"
                    className="button button--primary"
                    onClick={this.props.callback}
                >{this.props.label}</button>
            </div>
        )
    }
}
