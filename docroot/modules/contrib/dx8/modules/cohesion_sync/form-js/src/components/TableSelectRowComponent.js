import React from 'react'

/**
 * Wrapper for the table select row with checkbox.
 */
export default class TableSelectRowComponent extends React.Component {
    render () {
        return (
            <tr className="odd">
                <td>
                    <div
                        className="js-form-item form-item js-form-type-checkbox form-type-checkbox form-no-label">
                        <input
                            type="checkbox"
                            checked={this.props.defaultChecked()}
                            className="form-checkbox"
                            onChange={this.props.onClick}
                            data-identifier={this.props.inputName}
                        />
                    </div>
                </td>
                <td>{this.props.label}</td>
            </tr>
        )
    }
}
