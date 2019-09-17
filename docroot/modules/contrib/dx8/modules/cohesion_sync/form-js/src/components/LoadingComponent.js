import React from 'react'
import { LoadingOverlay, Loader } from 'react-overlay-loader'
import 'react-overlay-loader/styles.css'

/**
 * A semi-transparent overlay and loading animation wrapper.
 */
export default class LoadingComponent extends React.Component {
    render () {
        // The loading animation.
        const loadingMessage = <Loader loading={this.props.loading} classname="loader" text="Building package" />

        return (
            <LoadingOverlay className="package-loading-overlay">
                {loadingMessage}
                {this.props.children}
            </LoadingOverlay>

        )
    }
}
