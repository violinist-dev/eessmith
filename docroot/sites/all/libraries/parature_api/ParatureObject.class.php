<?php

require_once "ParatureAPI.class.php";

/**
 * Represents an object in Parature.
 *
 * Currently implemented classes are below, and can be created using
 * ParatureAPI()::get_object(object-name, ...);
 */
abstract class ParatureObject extends SimpleXMLElement { 
  /**
   * Gets xml of a field.
   *
   * Can get a field based on the name of a field's xpath as defined in the
   * object's implementation, the ID of a custom field, or the display name of
   * a custom field.
   *
   * @param string $field
   *   The name of the field to retrieve, or custom field ID/display name
   * @return SimpleXMLElement
   *   The value of the ticket field
   */
  public function get_field_xml($field) {
    // If the field looks like an ID, get the corresponding custom field
    if (is_numeric($field)) {
      $custom_field = $this->xpath('Custom_Field[@id=\'' . $field . '\']');
      $value = current($custom_field);
    }
    
    // Try to find standard object field
    $path = @constant($this->get_parature_name($this) . '::xpath_' . $field);
    
    if ($path != NULL)
      $value = current($this->xpath($path));
    
    // Field xpath isn't defined, check custom field display names.
    if (empty($value))
      $value = current($this->xpath('Custom_Field[@display-name=\'' . $field . '\']'));
    
    if (empty($value)) throw new Exception("Unable to find field \"$field\".");
    
    return $value;
  }
  
  /**
   * Gets the raw value of object's field.
   *
   * @param string $field
   *   The name of the ticket field to retrieve
   * @return string
   *   The value of the ticket field, or NULL if none exists.
   */
  public function get_field($field) {
    $field_xml = $this->get_field_xml($field);
    if ($field_xml->attributes()->{'data-type'} == 'option') {
      $option = current($field_xml->xpath('Option[@selected="true"]'));
      return (string)$option->Value;
    }
    return (string)$field_xml;
  }
  
  /**
   * Sets the value of a field.
   *
   * @param string $field
   *   The XML node name, custom field display name, or custom field ID of the
   *   field to set.
   * @param mixed $value
   *   The value to give the field.
   * @param bool $selected
   *   (optional) Whether to turn a select option On or Off. For multi-value
   *   option data types only.
   * @return SimpleXMLElement
   *  The altered XML node.
   */
  public function set_field($field, $value, $selected = TRUE) {
    $sxml = $this->get_field_xml($field);
    switch ((string)$sxml->attributes()->{'data-type'}) {
      
      case 'string':
        $sxml[0] = $value;
        break;
      
      case 'option':
        $this->_set_option($sxml, $value, $selected);
        break;
      
      default:
        return $sxml;
        break;
    }
    return $sxml;
  }
  
  /**
   * Gets the display name of a ticket field.
   *
   * @param string $field
   *   The name of the field to get the display name of.
   * @return string
   *   The field's display name, or NULL if none exists.
   */
  public function get_display_name($field) {
    return (string)current($this->xpath(constant(get_class($this) . '::xpath_' . $field) . '/@display-name'));
  }
  
  /**
   * Sets the ID attribute of an existing field.
   *
   * @param mixed $field
   *   The name of the field, according to get_field_xml().
   * @param int $id
   *   The new field ID.
   * @return SimpleXMLElement
   *   The altered XML node.
   */
  public function set_field_id($field, $id) {
    $sxml = $this->get_field_xml($field);
    
    if (empty($sxml->attributes()->id)) $sxml->addAttribute('id', $id);
    else $sxml->attributes()->id = $id;
    
    return $sxml;
  }
  
  /**
   * @return int
   *   The Parature ID of this object, or NULL if none exists.
   */
  public function get_id() {
    $id = $this->get_id_xml();
    return ($id == NULL ? NULL : (int)$id);
  }
  
  /**
   * @return SimpleXMLElement
   *   The XML node containing this object's Parature ID.
   */
  public function get_id_xml() {
    return $this->attributes()->id;
  }
  
  /**
   * Set the value of an option field.
   *
   * @param SimpleXMLElement $sxml
   *   The SimpleXMLElement of the field to set.
   * @param int $optionID
   *   The ID of the option to set.
   * @param bool $selected
   *   (optional) Whether to select or unselect the option where
   *   id == $optionID. For options which allow multiple values only.
   */
  protected function _set_option($sxml, $optionID, $selected = TRUE) {
    // Get an array of SimpleXMLElements, each representing an available option
    $options = $sxml->xpath('Option');
    
    // Iterate through available options and set them as necessary
    foreach ($options as $option) {
      // Set/unset the option
      if ($option->attributes()->id == $optionID) {
        if (empty($option->attributes()->selected) && $selected == TRUE)
          $option->addAttribute('selected', 'true');
        else {
          if ($selected == TRUE)
            $option->attributes()->selected = TRUE;
          else
            unset($option->attributes()->selected);
        }
      }
      // If this field can only have one option selected and it's being
      // selected, make sure there aren't other selected options
      elseif((string)$sxml->attributes()->{'multi-value'} == 'false'
             && $selected == TRUE
             && !empty($option->attributes()->selected)) {
        unset($option->attributes()->selected);
      }
    }
  }
  
/**
 * Build struct of field dependencies.
 * 
 * @param array &$parent_fields (optional) Array to fill with the names of
 *  fields that other fields/options are dependent on.
 * @return array AÊstruct of field dependency information in the following form:
 * 
 *   array[dependent_field_name] = array(
 *       '#parent' => parent_field_name,
 *       '#parent_value' => (for non-select fields only) parent_field_option_value,
 *       '#option_sets' (for select fields only) => array(
 *         parent_field_option_value => array(
 *           dependent_field_option_value => dependent_field_option_name,
 *           dependent_field_option_value => dependent_field_option_name,
 *           ...
 *         )
 *         parent_field_option_value => array(
 *           dependent_field_option_value => dependent_field_option_name,
 *           ...
 *         )
 *         ...
 *       )
 *     )
 */
  public function get_field_dependencies(&$parent_fields = array()) {
    $dependent_fields = array();
    $sxml_parent_fields = $this->xpath('//*[Option/Enables]');
    if (empty($sxml_parent_fields)) $sxml_parent_fields = array();
    
    // Iterate parent fields (fields that other fields are dependent upon)
    foreach ($sxml_parent_fields as $sxml_parent_field) {
      $parent_field_name = $sxml_parent_field->get_form_field_name();
      $parent_field_id = (string)$sxml_parent_field->attributes()->id;
      $sxml_parent_options = $sxml_parent_field->xpath('Option[Enables]');
      
      // Iterate parent options (options that enable other fields and/or options)
      foreach ($sxml_parent_options as $sxml_parent_option) {
        $parent_option_id = (string)$sxml_parent_option->attributes()->id;
        $sxml_enables_nodes = $sxml_parent_option->xpath('Enables');
        
        // Iterate <Enables> nodes (xml nodes containing xpaths to dependent fields and options)
        foreach($sxml_enables_nodes as $sxml_enables_node) {
          $xpath_nodes = explode('/', (string)$sxml_enables_node);
          $dependent_field_xpath = $xpath_nodes[2];
          $dependent_options_xpath = isset($xpath_nodes[3]) ? $xpath_nodes[2] . '/' . $xpath_nodes[3] : NULL;
          $sxml_dependent_field = current($this->xpath($dependent_field_xpath));
          $dependent_field_name = $sxml_dependent_field->get_form_field_name();
          $sxml_dependent_options = ($dependent_options_xpath != NULL) ? $this->xpath($dependent_options_xpath) : array();
          
          $dependent_fields[$dependent_field_name]['#parent'] = $parent_field_name;
          $parent_fields[] = $parent_field_name;
          
          // Iterate options dependent on the parent field
          foreach($sxml_dependent_options as $sxml_dependent_option) {
            $dependent_option_name = (string)$sxml_dependent_option->Value;
            $dependent_option_id = (string)$sxml_dependent_option->attributes()->id;
            $dependent_fields[$dependent_field_name]['#option_sets'][$parent_option_id][$dependent_option_id] = $dependent_option_name;
          }
          if (empty($dependent_fields[$dependent_field_name]['#option_sets'])) {
            $dependent_fields[$dependent_field_name]['#parent_value'] = $parent_option_id;
          }
        }
      }
    }
    return $dependent_fields;
  }

  /**
   * Get a form field name-friendly identifier for this object.
   *
   * Can be used for object fields within the root xml node.
   * 
   * @return string
   *   A unique form-safe field name such as Customer_Cc_List or
   *   Custom_Field-117.
   */
  public function get_form_field_name() {
    $object_field = $this;
    $name = (string)$object_field->getName();
    if ($name == "Custom_Field") {
      $name .= '-' . $object_field->attributes()->id;
    }
    return $name;
  }

  /**
   * @return string
   *   The Parature name, usable in API request URIs, of this object.
   */
  public function get_parature_name() {
    switch (get_class($this)) {
      case 'TicketAction':
        return 'Ticket';
      default:
        return get_class($this);
    }
  }
}

/**
 * A ticket in Parature
 */
class Ticket extends ParatureObject {
  // XPath queries for ticket fields
  const xpath_Assigned_To         = 'Assigned_To/Csr';
  const xpath_Cc_Csr              = 'Cc_Csr';
  const xpath_Cc_Customer         = 'Cc_Customer';
  const xpath_Date_Created        = 'Date_Created';
  const xpath_Date_Updated        = 'Date_Updated';
  const xpath_Department          = 'Department/Department';
  const xpath_Email_Notification  = 'Email_Notification';
  const xpath_Email_Notification_Additional_Contact = 'Email_Notification_Additional_Contact';
  const xpath_Entered_By          = 'Entered_By/Csr';
  const xpath_Hide_From_Customer  = 'Hide_From_Customer';
  const xpath_Ticket_Customer     = 'Ticket_Customer/Customer';
  const xpath_Ticket_Number       = 'Ticket_Number';
  const xpath_Ticket_Status       = 'Ticket_Status/Status';
}

/**
 * A customer in Parature
 */
class Customer extends ParatureObject {
  // XPath queries for customer fields
  const xpath_Account             = 'Account';
  const xpath_Customer_Role       = 'Customer_Role/CustomerRole';
  const xpath_Date_Created        = 'Date_Created';
  const xpath_Date_Updated        = 'Date_Updates';
  const xpath_Date_Visited        = 'Date_Visited';
  const xpath_Email               = 'Email';
  const xpath_First_Name          = 'First_Name';
  const xpath_Last_Name           = 'Last_Name';
  const xpath_SLA                 = 'Sla/Sla';
  const xpath_Status              = 'Status/Status';
  const xpath_User_Name           = 'User_Name';
}

/**
 * A CSR in Parature
 */
class Csr extends ParatureObject {
  const xpath_Name                = 'Full_Name';
  const xpath_Email               = 'Email';
  const xpath_Phone_1             = 'Phone_1';
  const xpath_Phone_2             = 'Phone_2';
  const xpath_Timezone            = 'Timezone/Timezone/Abbreviation';
}

/**
 * A ticket status in Parature
 */
class TicketStatus extends ParatureObject {
  const xpath_Customer_Text       = 'Customer_Text';
  const xpath_Name                = 'Name';
}

/**
 * An Account in Parature
 */
class Account extends ParatureObject {
  const xpath_Name                = 'Account_Name';
  const xpath_Date_Created        = 'Date_Created';
  const xpath_Date_Modified       = 'Date_modified';
  const xpath_SLA                 = 'Sla/Sla';
}

/**
 * An asset in Parature
 */
class Asset extends ParatureObject {
  const xpath_Name                = 'Name';
  const xpath_Account             = 'Account_Owner/Account/Account_Name';
}

/**
 * A ticket action in Parature (used as a POST value when performing ticket actions)
 */
class TicketAction extends ParatureObject {
}
