import React from 'react'
import Store from '../Store'

import { DetailsContainer, TableSelectContainer } from '../containers'
import { ApplyButtonComponent, LoadingComponent } from '../components'

/**
 * Excluded entity types app components (will be placed into a Drupal accordion).
 */
export default class ExcludedEntityTypesApp extends React.Component {
    render () {
        return (
            <Store.Subscribe>
                {store => {
                    // Render the requirements accordion contents.
                    return (
                        <LoadingComponent loading={store.state.loading}>
                            <TableSelectContainer
                                key='excluded'
                                dataKey='excludedDataKey'
                                data={{
                                    items: store.state.excludedEntityTypesForm,
                                    label: 'Entity type'
                                }}
                                uuidStatusCallback={(entityTypeId) => store.hasExcluded(entityTypeId)}
                                uuidChangeCallback={(entityTypeId) => store.toggleExcluded(entityTypeId)}
                            />
                            {Object.keys(store.state.excludedEntityTypesForm).length ?
                                <ApplyButtonComponent
                                    dataAction="excluded"
                                    label='Set excluded entity types »'
                                    callback={() => store.refresh()}
                                /> : false}
                        </LoadingComponent>
                    )
                }}
            </Store.Subscribe>
        )
    }
}
