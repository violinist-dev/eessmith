import React from 'react'
import Store from '../Store'
import { DetailsContainer } from '../containers'
import { LoadingComponent, EntityLabelComponent } from '../components'

/**
 * Array of entity contents (grouped by entity type from parent).
 */
class EntityTypesGroupComponent extends React.Component {
    render () {
        return (
            <Store.Subscribe>
                {store => {
                    let requirements = []
                    Object.keys(this.props.data).forEach((key) => {
                        requirements.push(
                            <DetailsContainer
                                key={key}
                                dataKey={key}
                                data={this.props.data[key]}
                                uuidStatusCallback={(uuid) => store.hasEntityContent(key, uuid)}
                                uuidChangeCallback={(uuid, type) => store.toggleEntityContent(key, uuid, type)}
                            />
                        )
                    })

                    return (
                        <>
                        {requirements.length ? requirements : 'No entities selected.'}
                        </>
                    )
                }}
            </Store.Subscribe>
        )
    }
}

/**
 * Package contents app components (will be placed into a Drupal accordion).
 */
export default class PackageContentsApp extends React.Component {
    render () {
        return (
            <Store.Subscribe>
                {store => {
                    // Render the list of entities types and containing <details>
                    let requirements = []
                    Object.keys(store.state.packageContentsForm).forEach((key) => {
                        requirements.push(
                            <div key={key}>
                                <EntityLabelComponent label={store.state.packageContentsForm[key].label} />
                                <EntityTypesGroupComponent
                                    key={key}
                                    data={store.state.packageContentsForm[key].entities}
                                >
                                    {this.props.children}
                                </EntityTypesGroupComponent>
                            </div>
                        )
                    })

                    // Render the requirements accordion contents.
                    return (
                        <LoadingComponent loading={store.state.loading}>
                            {requirements.length ? requirements : 'No entities selected.'}
                        </LoadingComponent>
                    )
                }}
            </Store.Subscribe>
        )
    }
}