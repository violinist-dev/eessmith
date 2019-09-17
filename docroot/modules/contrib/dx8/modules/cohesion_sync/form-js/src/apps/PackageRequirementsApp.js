import React from 'react'
import Store from '../Store'
import { VerticalTabsContainer, TabButtonLabelContainer, TabContentContainer } from '../containers'
import { ApplyButtonComponent, LoadingComponent } from '../components'

/**
 * Package requirements app components (will be placed into a Drupal accordion).
 */
export default class PackageRequirementsApp extends React.Component {
    render () {
        return (
            <Store.Subscribe>
                {store => {
                    let tabContent = []
                    let tabButtons = []

                    // Render the lits of tab buttons.
                    Object.keys(store.state.packageRequirementsForm).forEach((key) => {
                        // Create the tab buttons.
                        tabButtons.push(
                            <TabButtonLabelContainer
                                key={key}
                                dataKey={key}
                                data={store.state.packageRequirementsForm[key]}
                                uuidStatusCallback={(uuid) => store.hasEntityRequirement(uuid)}
                                uuidChangeCallback={(uuid, type) => store.toggleEntityRequirement(uuid, type)}
                            />
                        )

                        // Render the list of tab contents.
                        tabContent.push(
                            <TabContentContainer
                                key={key}
                                dataKey={key}
                                data={store.state.packageRequirementsForm[key]}
                                uuidStatusCallback={(uuid) => store.hasEntityRequirement(uuid)}
                                uuidChangeCallback={(uuid, type) => store.toggleEntityRequirement(uuid, type)}
                            />
                        )
                    })

                    // Render the requirements section.
                    return (
                        <LoadingComponent loading={store.state.loading}>
                            <ApplyButtonComponent
                                dataAction="apply"
                                label='Build package »'
                                callback={() => store.refresh()}
                            />
                            <VerticalTabsContainer tabButtons={tabButtons} tabContent={tabContent} />
                        </LoadingComponent>
                    )
                }}
            </Store.Subscribe>
        )
    }
}