<?php
/**
 * Definiciones de texto para la API REST
 * 
 * Este archivo contiene todas las constantes de texto utilizadas
 * en las respuestas de la API. Están organizadas por categorías
 * funcionales para facilitar su mantenimiento y localización.
 * 
 * @author  [Francisco Lopez Sanchez]
 * @version 1.0
 */

/**
 * Mensajes relacionados con la autenticación por token
 */
define('NO_TOKEN_MESSAGE','El token no es válido');

/**
 * Mensajes relacionados con la gestión de usuarios
 * Incluye respuestas para operaciones de creación, edición y eliminación,
 * así como mensajes de error por falta de permisos.
 */
define('ADD_USER_OK', 'Usuario creado con éxito');
define('ADD_USER_KO', 'Ocurrió un fallo al crear el usuario');
define('ADD_USER_NOT_PERMISION', 'No dispone de permisos para añadir un usuario');

define('EDIT_USER_OK','Usuario editado con éxito');
define('EDIT_USER_KO','Ocurrió un fallo al editar el usuario');
define('EDIT_USER_NOT_PERMISION','No dispone de permisos para editar un usuario');

define('DELETE_USER_OK', 'Usuario eliminado con éxito');
define('DELETE_USER_KO', 'Ocurrió un fallo al eliminar el usuario');
define('DELETE_USER_NOT_PERMISION', 'No dispone de permisos para eliminar un usuario');

define('EDIT_PERFIL_OK','Perfil de usuario editado con éxito');
define('EDIT_PERFIL_KO','Ocurrió un fallo al editar el perfil de usuario');

/**
 * Mensajes relacionados con la gestión de empresas
 * Incluye respuestas para operaciones de creación, edición y eliminación.
 */
define('ADD_EMPRESA_OK', 'Empresa creada con éxito');
define('ADD_EMPRESA_KO', 'Ocurrió un fallo al crear la empresa');

define('EDIT_EMPRESA_OK','Empresa editada con éxito');
define('EDIT_EMPRESA_KO','Ocurrió un fallo al editar la empresa');

define('DELETE_EMPRESA_OK', 'Empresa eliminada con éxito');
define('DELETE_EMPRESA_KO', 'Ocurrió un fallo al eliminar la empresa');

/**
 * Mensajes relacionados con la gestión de proyectos
 * Incluye respuestas para operaciones de creación, edición y eliminación.
 */
define('ADD_PROYECTO_OK', 'Proyecto creado con éxito');
define('ADD_PROYECTO_KO', 'Ocurrió un fallo al crear el proyecto');

define('EDIT_PROYECTO_OK','Proyecto editado con éxito');
define('EDIT_PROYECTO_KO','Ocurrió un fallo al editar el proyecto');

define('DELETE_PROYECTO_OK', 'Proyecto eliminado con éxito');
define('DELETE_PROYECTO_KO', 'Ocurrió un fallo al eliminar el proyecto');

/**
 * Mensajes relacionados con la gestión de accesos
 * Incluye respuestas para operaciones de asignación y eliminación de accesos.
 */
define('ADD_ACCESO_OK', 'Acceso asignado con éxito');
define('ADD_ACCESO_KO', 'Error al asignar el acceso');

define('DELETE_ACCESO_OK', 'Acceso eliminado con éxito');
define('DELETE_ACCESO_KO', 'Error al eliminar el acceso');
?>
