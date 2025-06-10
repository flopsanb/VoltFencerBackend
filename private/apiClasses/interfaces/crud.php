<?php
/**
 * Interfaz CRUD (Create, Read, Update, Delete)
 * 
 * Esta interfaz define los métodos estándar que deben implementar
 * todas las clases que proporcionen operaciones básicas de manipulación
 * de datos en el sistema. Establece un contrato común para las
 * funcionalidades de creación, lectura, actualización y eliminación.
 * 
 * @author  [Francisco Lopez Sanchez]
 * @version 1.0
 */

interface crud
{
    /**
     * Recupera registros de la entidad correspondiente
     * 
     * Este método debe implementar la lógica para obtener uno o más registros
     * de la entidad, dependiendo de la implementación específica.
     * 
     * @return mixed Los registros recuperados o null si no se encuentran
     */
    public function get();

    /**
     * Crea un nuevo registro con los datos proporcionados
     * 
     * Este método debe implementar la lógica para validar y almacenar
     * un nuevo registro de la entidad en la base de datos.
     * 
     * @param array $data Datos para crear el nuevo registro
     * @return mixed El resultado de la operación de creación
     */
    public function create($data);

    /**
     * Actualiza un registro existente con los datos proporcionados
     * 
     * Este método debe implementar la lógica para validar y actualizar
     * un registro existente en la base de datos.
     * 
     * @param array $data Datos para actualizar el registro, debe incluir identificador
     * @return mixed El resultado de la operación de actualización
     */
    public function update($data);

    /**
     * Elimina un registro específico por su identificador
     * 
     * Este método debe implementar la lógica para validar y eliminar
     * un registro existente de la base de datos.
     * 
     * @param mixed $id Identificador único del registro a eliminar
     * @return mixed El resultado de la operación de eliminación
     */
    public function delete($id);
}

?>