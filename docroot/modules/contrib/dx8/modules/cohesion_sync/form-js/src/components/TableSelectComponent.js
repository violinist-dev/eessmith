import React from 'react'

/**
 * Wrapper for the table select widget.
 */
export default class TableSelectComponent extends React.Component {
    render () {
        return (
            <table className="responsive-enabled">
                <thead>
                <tr>
                    <th className="select-all-input">
                        <input
                            type="checkbox"
                            className="form-checkbox"
                            title="Toggle all rows in this table"
                            checked={this.props.defaultChecked}
                            onChange={this.props.onClick}
                        />
                    </th>
                    <th>{this.props.label}</th>
                </tr>
                </thead>

                <tbody>
                {this.props.children}
                </tbody>
            </table>
        )
    }
}
