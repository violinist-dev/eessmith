import React from 'react'

import { TabButtonItemComponent, VerticalTabsComponent } from '../components'

/**
 * An implementation of Drupal style vertical tabs.
 */
export default class VerticalTabsContainer extends React.Component {
    /**
     * Set up the initial component state.
     */
    constructor () {
        super()

        // The toggle state is used so that we only render the contents whent he accordion is opened.
        this.state = {
            openTabIndex: 0
        }
    }

    /**
     * Switch tabs.
     * @param index
     */
    setIndex (index) {
        this.setState({openTabIndex: index})
    }

    /**
     * Render the component.
     * @returns {*}
     */
    render () {
        // Build the tabs list.
        let buttonList = []
        this.props.tabButtons.forEach((item, i) => {
            const href = `#edit-${item.key}`

            buttonList.push(
                <TabButtonItemComponent
                    isSelected={i === this.state.openTabIndex}
                    href={href}
                    onClick={() => {
                        this.setIndex(i)
                        return false
                    }}
                    renderItem={item}
                    key={i}
                />
            )

        })

        // Render the all the tab buttons and the active contents.
        if (this.props.tabContent.length) {
            return (
                <VerticalTabsComponent
                    buttonList={buttonList}>
                    {typeof this.props.tabContent[this.state.openTabIndex] ? this.props.tabContent[this.state.openTabIndex] : 'No content'}
                </VerticalTabsComponent>
            )
        }
        // The list was empty, so show nothing.
        else {
            return (<div />)
        }
    }
}