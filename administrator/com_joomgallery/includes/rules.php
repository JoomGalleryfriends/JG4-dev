<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

/**
 * Array for mapping the callable rule names to actual acl rules
 * and to define available assets (content types) and the own tag.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
$rules_map_array = array
(
  'add'       => array(   'name'   => 'add',
                          'rule'   => 'core.create', 
                          'assets' => array(  '.', 
                                              '.image', 
                                              '.category',
                                              '.config',
                                              '.tag'
                                            ),
                          'own'    => 'inown'
                      ),

  'admin'     => array(   'name'   => 'admin',
                          'rule'   => 'core.admin',
                          'assets' => array(  '.'
                                            ),
                          'own'    => false
                      ),

  'delete'    => array(   'name'   => 'delete', 
                          'rule'   => 'core.delete',
                          'assets' => array(  '.',
                                              '.image', 
                                              '.category',
                                              '.config',
                                              '.tag'
                                            ),
                          'own'    => 'own'
                      ),

  'edit'      => array(   'name'   => 'edit',
                          'rule'   => 'core.edit',
                          'assets' => array(  '.',
                                              '.image',
                                              '.category',
                                              '.config',
                                              '.tag'
                                            ),
                          'own'    => 'own'
                      ),

  'editstate' => array(   'name'   => 'editstate',
                          'rule'   => 'core.edit.state',
                          'assets' => array(  '.',
                                              '.image',
                                              '.category',
                                              '.config',
                                              '.tag'
                                            ),
                          'own'    => false
                      ),

  'manage'    => array(   'name'   => 'manage',
                          'rule'   => 'core.manage', 
                          'assets' => array(  '.'
                                            ),
                          'own'    => false
                      )
);