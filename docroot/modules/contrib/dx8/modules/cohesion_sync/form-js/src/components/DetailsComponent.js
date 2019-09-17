import React from 'react'

/**
 * Wrapper for a <details> and <summary> accordion.
 */
export default class DetailsComponent extends React.Component {
    constructor () {
        super()

        // The toggle state is used so that we only render the contents whent he accordion is opened.
        this.state = {
            opened: false
        }
    }

    componentWillMount () {
        // If the element is empty, add a message.
        if (!this.props.children.length) {
            this.props.children.push(
                <table className="responsive-enabled" key="noitems">
                    <tbody>
                    <tr>
                        <td>No items.</td>
                    </tr>
                    </tbody>
                </table>
            )
        }
    }

    toggleState () {
        this.setState({opened: !this.state.opened})
    }

    render () {
        return (
            <details
                className="js-form-wrapper form-wrapper package-details"
                open={this.state.opened}
                ref={this.DetailsDomState}
                onToggle={() => this.toggleState()}
            >
                <summary
                    role="button"
                    aria-controls="unknown"
                    aria-expanded="true"
                    aria-pressed="true"
                    className="details__summary"
                >
                    {this.props.label}
                </summary>
                <div>
                    {this.state.opened ? this.props.children : false}
                </div>
            </details>
        )
    }
}
