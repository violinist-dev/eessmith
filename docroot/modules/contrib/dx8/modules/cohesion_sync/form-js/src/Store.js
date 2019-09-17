import React from 'react'
import { Provider, Subscribe, Container } from 'unstated' // Import helpers from Unstated
import axios from 'axios'
import deepcopy from 'deepcopy'
import traverse from 'traverse'
import { diff, addedDiff, deletedDiff, updatedDiff, detailedDiff } from 'deep-object-diff'

/**
 * StoreContainer holds shared/global state and methods
 */
class StoreContainer extends Container {
    /**
     * Define the state and set up the forms.
     */
    constructor () {
        super()

        // Shared props
        this.state = {
            loading: false,
            excludedEntityTypesForm: {},
            packageRequirementsForm: {},
            packageContentsForm: {},
            excludedSettings: !Array.isArray(drupalSettings.syncPackageForm.excludedSettings) && drupalSettings.syncPackageForm.excludedSettings !== false ? drupalSettings.syncPackageForm.excludedSettings : {},
            packageSettings: !Array.isArray(drupalSettings.syncPackageForm.packageSettings) && drupalSettings.syncPackageForm.packageSettings !== false ? drupalSettings.syncPackageForm.packageSettings : {}
        }

        // Copy the store data to the Drupal hidden fields on submit.
        const formElement = document.getElementsByClassName('cohesion-sync-package-form').item(0)

        formElement.addEventListener('submit', (e) => {
            e.preventDefault()

            // Copy the Store settings to the Drupal form hidden fields.
            document.querySelectorAll('[data-drupal-selector="edit-package-settings"]').item(0).value = JSON.stringify(this.cleanForSave())
            document.querySelectorAll('[data-drupal-selector="edit-excluded-settings"]').item(0).value = JSON.stringify(this.state.excludedSettings)

            // And submit the form again programatically, bypassing this event listener.
            formElement.submit()
            return false
        }, false)

        // Initial setup of the form data.
        this.refresh(true)
    }

    /**
     * Is an entity type excluded?
     *
     * @param entityTypeId
     * @returns {boolean}
     */
    hasExcluded (entityTypeId) {
        return typeof this.state.excludedSettings[entityTypeId] !== 'undefined'
    }

    async toggleExcluded (entityTypeId) {
        let tempExcludedSettings = this.state.excludedSettings

        // Entry exists so remove it from the settings.
        if (this.hasExcluded(entityTypeId)) {
            delete tempExcludedSettings[entityTypeId]
        }
        // Entry doesn't exist, so add a blank entry.
        else {
            tempExcludedSettings[entityTypeId] = true
        }

        // And set the state
        await this.setState({excludedSettings: tempExcludedSettings})
    }

    /**
     * Does a requirement exist in the store?
     * @param uuid
     * @returns {boolean}
     */
    hasEntityRequirement (uuid) {
        if (typeof this.state.packageSettings[uuid] !== 'undefined') {
            if (this.state.packageSettings[uuid].checked === true) {
                // Only checked if it can be found and is true.
                return true
            }
        }

        return false
    }

    /**
     * Turn a requirement on/off.
     * @param uuid
     * @param type
     */
    async toggleEntityRequirement (uuid, type) {
        let tempPackageSettings = this.state.packageSettings

        // Add the entry if it doesn't already exist in the settings.
        if (typeof tempPackageSettings[uuid] === 'undefined') {
            tempPackageSettings[uuid] = {
                type,
                items: {},
                checked: false  // This immediately gets overwritten below.
            }
        }

        // Toggle/set the setting.
        tempPackageSettings[uuid].checked = !this.hasEntityRequirement(uuid)

        // And set the state
        await this.setState({packageSettings: tempPackageSettings})
    }

    /**
     * Has the dependency been checked?
     * @param requirementUuid
     * @param uuid
     * @returns {boolean}
     */
    hasEntityContent (requirementUuid, uuid) {
        // Make sure the top level entity exists in the store.
        if (typeof this.state.packageSettings[requirementUuid] !== 'undefined') {
            // Return the state.
            return typeof this.state.packageSettings[requirementUuid].items[uuid] !== 'undefined'
        }

        return false
    }

    /**
     * Turn a dependency (contents) on/off.
     * @param requirementUuid
     * @param uuid
     * @param type
     */
    async toggleEntityContent (requirementUuid, uuid, type) {
        // Make sure the top level entity exists in the store.
        if (typeof this.state.packageSettings[requirementUuid] !== 'undefined') {
            let tempPackageSettings = this.state.packageSettings

            if (this.hasEntityContent(requirementUuid, uuid)) {
                delete tempPackageSettings[requirementUuid].items[uuid]
            }
            // Entry doesn't exist, so add a blank entry.
            else {
                tempPackageSettings[requirementUuid].items[uuid] = {
                    type
                }
            }

            await this.setState({packageSettings: tempPackageSettings})
        }
    }

    /**
     * Call Drupal with the settings in the store variables to update the forms.
     *
     * @param initial
     * @returns {Promise<boolean>}
     */
    async refresh (initial = false) {
        let res
        let cleanedSettings = this.cleanSettings() // Clean the settings of unchecked items.

        // Initially loading.
        await this.setState({loading: true})

        // Ask Drupal to re-calculate the requirements and contents form
        try {
            res = await axios.post(`/admin/cohesion/sync/refresh`, {
                packageSettings: cleanedSettings,
                excludedSettings: this.state.excludedSettings
            })
        } catch (e) {
            // @todo - this could do with a more meaningful catch() for when axios fails.
            console.warn(`Failed to access the Drupal endpoint /admin/cohesion/sync/refresh`, e.message)
            return false
        }

        // Check the return code was okay.
        if (res.status === 200) {
            // Finished loading.
            await this.setState({loading: false})

            // Build a list of new items added to packageContentsForm
            let newPackageContentFormDiff = {}
            if (!initial) {
                newPackageContentFormDiff = addedDiff(this.state.packageContentsForm, res.data.packageContentsForm)
            }

            // Copy forms to the store.
            await this.setState({
                packageSettings: cleanedSettings,
                packageRequirementsForm: res.data.packageRequirementsForm,
                packageContentsForm: res.data.packageContentsForm,
                excludedEntityTypesForm: res.data.excludedEntityTypesForm
            })

            // Turn on all the new checkboxes.
            if (!initial) {
                this.setNewToChecked(newPackageContentFormDiff)
            }

            return true
        }
    }

    /**
     * Check all new items that are added to the packageContentsForm.
     */
    setNewToChecked (newPackageContents) {
        const context = this

        traverse(newPackageContents).forEach(function (item) {
            if (typeof item.type !== 'undefined') {
                // @todo - this is not waiting for the promise to finish :/
                context.toggleEntityContent(this.parent.parent.parent.parent.key, this.key, item.type)
            }
        }, context)
    }

    /**
     * Clean the package settings of all unchecked items.
     * @returns {*}
     */
    cleanSettings () {
        // First clone the object.
        let cleanSettings = deepcopy(this.state.packageSettings)

        // Delete any `checked=false` entries (@todo - using traverse for this is total overkill)
        traverse(cleanSettings).forEach(function (item) {
            if (typeof item.checked !== 'undefined') {
                if (item.checked === false) {
                    this.delete()
                }
            }
        })

        return cleanSettings
    }

    /**
     * Clean the package settings of all items checked in requirements but not in contents.
     * @returns {*}
     */
    cleanForSave() {
        let cleanSettings = deepcopy(this.state.packageSettings)
        traverse(cleanSettings).forEach(function (item) {
            if (typeof item.checked !== 'undefined') {
                // Delete item if checked but nothing selected.
                if (item.checked === true && Object.keys(item.items).length === 0) {

                    console.log(JSON.stringify(item))

                    this.delete()
                }
            }
        })

        return cleanSettings
    }

}

// Instantiate the Store Container
const instance = new StoreContainer()

// Wrap the Provider
const StoreProvider = props => {
    return <Provider inject={[instance]}>{props.children}</Provider>
}

// Wrap the Subscriber
const StoreSubscribe = props => {
    return <Subscribe to={[instance]}>{props.children}</Subscribe>
}

// Export wrapped Provider and Subscriber
export default {
    Provider: StoreProvider,
    Subscribe: StoreSubscribe
}