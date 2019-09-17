import React from 'react'
import ReactDOM from 'react-dom'

// Include the stylesheet (webpack compiles and attaches this automatically using the scss loader).
import './scss/index.scss'

// Import the shared store.
import Store from './Store'

// And the app components.
import { ExcludedEntityTypesApp, PackageRequirementsApp, PackageContentsApp } from './apps'

// Gain access to drupalSettings to read the initial form data.
(function (Drupal, drupalSettings) {
    // Render the top level app components into their Drupal accordion containers.
    ReactDOM.render(
        <Store.Provider>
            <ExcludedEntityTypesApp/>
        </Store.Provider>,
        document.getElementById('package-excluded-container')
    )

    ReactDOM.render(
        <Store.Provider>
            <PackageRequirementsApp/>
        </Store.Provider>,
        document.getElementById('package-requirements-container')
    )

    ReactDOM.render(
        <Store.Provider>
            <PackageContentsApp/>
        </Store.Provider>,
        document.getElementById('package-contents-container')
    )

})(Drupal, drupalSettings)
