<?php

namespace Framework\Core;

use Framework\Core\interfaces\DatabaseInterface;
use PDO;

/**
 * File: Database.php
 * Author: David@Refoua.me
 * Author: kouroshmoshrefi@hotmail.com
 * Version: 0.1.0
 */

class Database implements DatabaseInterface
{
    /**
     * @var PDO instance to the db connection
     */
    private readonly PDO $connection;

    /**
     * Open the database connection
     *
     * @param string $dsn       Connection data source name
     * @param string $username  Database username
     * @param string $password  Authentication password
     */

    public function __construct(string $dsn, string $username = '', string $password = '')
    {
        // Check if all the required extensions are present
        foreach ( ['PDO', 'pdo_mysql'] as $extension ) if( !extension_loaded($extension) ) {
            throw new \Exception("The required '$extension' extension is not enabled.");
        }

        try {

            // Remove all whitespace, tabs and newlines
            $dsn = preg_replace( '|\s+|', '', $dsn );

            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,		// turn on errors in the form of exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,			// make the default fetch be an associative array
                PDO::ATTR_EMULATE_PREPARES   => false						// turn off emulation mode for "real" prepared statements
            ]);

            // operate in UTF-8 character set
            $this->connection->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");

        }

        catch( \PDOException $exception ) {
            $this->connection = NULL;

            throw $exception;
        }

    }

    /**
     * Get a single row from the database
     *
     * @param string tbl_name   Name of the table
     * @param array  filters    List of key value columns
     *
     * @return array Associative array containing the row data
     */

    public function getRow(string $tbl_name, array $filters) : array
    {
        $rows = $this->readAll( $tbl_name, $filters, 1 );
        return empty($rows) ? $rows : reset($rows);
    }

    /**
     * Read all rows from the database that match the filters
     *
     * @param string tbl_name   Name of the table
     * @param array  filters    List of key value columns
     * @param int    limit      Maximum number of rows to return, INF for uno limit
     *
     * @return array An array containing an associative array per returned row
     */

    public function readAll(string $tbl_name, array $filters, int $limit = 0) : array
    {
        $table            = self::sanitizeName    ( $tbl_name );
        $limit            = self::sanitizeInt     ( $limit );
        $filters          = self::sanitizeArray   ( $filters );
        $sanitizedFilters = self::sanitizeFilters ( $filters );

        $fields  = '*'; // TODO: select certain columns only
        $where   = self::buildWhere( $filters );
        $sql     = "SELECT $fields FROM `$table` WHERE ($where)".($limit>0 ? " LIMIT $limit" : '');
        $stmt    = $this->connection->prepare( self::formatSQL($sql, $this->connection) );
        $success = $stmt->execute( $sanitizedFilters );
        $result  = $stmt->fetchAll( PDO::FETCH_ASSOC );
        // $count   = $stmt->rowCount();
        return $result;
    }

    /**
     * Update existing columns with new data
     *
     * @param string tbl_name   Name of the table
     * @param array  filters    List of key value columns to match
     * @param array  data       List of key value columns to update
     *
     * @return int the number of affected rows
     */

    public function write(string $tbl_name, array $filters, array $data) : int
    {
        $table   = self::sanitizeName  ( $tbl_name );
        $filters = self::sanitizeArray ( $filters );
        $data    = self::sanitizeArray ( $data );

        $post    = [];
        $clause  = implode(', ', self::preparePost( $post, $data, 'set' )[1] );
        $where   = implode(' AND ', self::preparePost( $post, $filters, 'where' )[1] );
        $sql     = ("UPDATE `$table` SET $clause WHERE ($where)");
        $stmt    = $this->connection->prepare( self::formatSQL($sql, $this->connection) );
        $success = $stmt->execute( $post );
        $count   = $stmt->rowCount();
//        To make return value compatible with function return type
//        return $success ? $count : null;
        return $success ? $count : 0;
    }

    /**
     * Insert a new row in the database
     *
     * @param string tbl_name   Name of the table
     * @param array  data       List of key value columns
     *
     * @return int the last insert id if successful, null otherwise
     */

    public function addRow(string $tbl_name, array $data) : int
    {
        $table   = self::sanitizeName  ( $tbl_name );
        $data    = self::sanitizeArray ( $data );

        list($fields, $values) = self::preparePost( $post, $data, 'insert', false );
        $fields  = implode(', ', array_map(fn($x) => "`{$x}`", $fields) );
        $values  = implode(', ', $values );
        $sql     = ("INSERT INTO `$table` ($fields) VALUES ($values)");
        $stmt    = $this->connection->prepare( self::formatSQL($sql, $this->connection) );
        $success = $stmt->execute( $post );
        $id      = $this->connection->lastInsertId();
//        To make return value compatible with function return type
//        return $success ? $id : null;
        return $success ? $id : -1;
    }

    /**
     * Return the number of matching rows
     *
     * @param string tbl_name   Name of the table
     * @param array  filters    List of key value columns
     *
     * @return int number of rows if successful, null otherwise
     */

    public function count(string $tbl_name, array $filters) : int
    {
        $table            = self::sanitizeName    ( $tbl_name );
        $filters          = self::sanitizeArray   ( $filters );
        $sanitizedFilters = self::sanitizeFilters ( $filters );

        $fields  = '*';
        $where   = self::buildWhere( $filters );
        $sql     = ("SELECT COUNT($fields) FROM `$table` WHERE ($where)");
        $stmt    = $this->connection->prepare( self::formatSQL($sql, $this->connection) );
        $success = $stmt->execute( $sanitizedFilters );
        $column  = $stmt->fetchColumn();
//        To make return value compatible with function return type
//        return $success ? $column : null;
        return $success ? $column : 0;
    }

    /**
     * Check if matching rows exist
     *
     * @param string tbl_name   Name of the table
     * @param array  filters    List of key value columns
     *
     * @return int true/false if successful, null otherwise
     */

    public function exists(string $tbl_name, array $filters) : bool
    {
        $count = $this->count($tbl_name, $filters);
        return is_null($count) ? $count : ($count > 0);
    }

    /**
     * Perform SQL query and return rows from database
     *
     * @param string query        Full SQL query to perform
     * @param array  arguments    List of arguments to pass to the database
     *
     * @return array matched rows if successful, null otherwise
     */

    public function query(string $query, array $arguments = []) : array
    {
        $stmt    = $this->connection->prepare( formatSQL($query) );
        $success = $stmt->execute( $arguments );
        $result  = $stmt->fetchAll( PDO::FETCH_ASSOC );
//        To make return value compatible with function return type
//        return $success ? $result : null;
        return $success ? $result : [];
    }

    /**
     * Execute SQL command in database
     *
     * @param string query        Full SQL command to execute
     * @param array  arguments    List of arguments to pass to the database
     *
     * @return boolean true if successful, false otherwise
     */

    public function execute(string $query, array $arguments = []) : bool
    {
        $stmt    = $this->connection->prepare( formatSQL($query) );
        $success = $stmt->execute( $arguments );
        return $success;
    }

    public static function array_build( $glue, $array ) {
        $output = [];
        foreach ( $array as $key=>$value ) $output []= implode( $glue, array($key, $value) );
        return $output;
    }

    public static function array_use( $pattern, $array ) {
        $output = []; foreach ( $array as $key=>$value ) $output [] =
        str_replace('?', $key, str_replace('*', $value, $pattern));
        return $output;
    }

    /*
    public static function array_set( $replacement, $array ) {
        $output = []; foreach ( $array as $key=>$value )
        $output [    str_replace('@', $key, $replacement) ]=
                    str_replace('*', $value, $replacement);
        return $output;
    }
    */

    public static function array_set( $replacement, $array ) {
        return array_map(function($key) use(&$replacement) {
            return str_replace('*', $key, $replacement);
        }, $array);
    }

    /*
    public static function array_set( $replacement, $array ) {
        $output = ( preg_match('|[\@\*]|', $replacement) ) ?
        function( $output = [] ) use(&$replacement, &$array) {
            foreach ( $array as $key=>$value ) $output [str_replace('@', $key, $replacement)]= str_replace('*', $value, $replacement);
            return $output;
        } :
        array_map(function($key) use(&$replacement) {
            return str_replace('*', $key, $replacement);
        }, $array);
        return $output;
    }

    public static function array_remap( $replacement, $array ) {
        $output = [];
        foreach ( $array as $key=>$value ) $output [str_replace('*', $key, $replacement)]= ($value);
        return $output;
    }
    */

    public static function sanitizeInt( string|int $input ) : int {
        // Return null, negative and positive Infinity as-is
        if ( is_null($input) || is_int($input) ) return $input;

        // Remove any kind of whitespace, and/or comma digit separators
        $input = preg_replace( '/[\s|\,]+/', '', (string) $input );

        return intval($input);
    }

    public static function sanitizeOpr( string $input ) : string {
        // Trim all whitespace characters
        $input = trim($input);

        // Truncate to 128 characters
        $input = substr( $input, 0, 128 ); // TODO: replace with self::MAX_LEN or something

        return $input;
    }

    public static function sanitizeName( string $input ) : string {
        $output = trim($input);

        // Change any whitespace to underscore characters
        $output = preg_replace( '/\s+/', '_', $output );

        // Remove all non-alphanumeric and underscore characters
        $output = preg_replace( '/[^\w\-]+/', '', $output );

        // Truncate to 128 characters
        $output = substr( $output, 0, 128 ); // TODO: replace with self::MAX_LEN or something

        return $output;
    }

    public static function sanitizeType( string $input ) : string {
        // Remove all invalid characters
        $input = preg_replace( '/[^\w\s\(\)]+/', '', $input );

        // Remove excess white-spaces
        $input = preg_replace( '/[\s]+/', ' ', $input );

        return trim($input);
    }

    public static function sanitizeArray( array $input ) : array {
        $output = [];
        foreach ( $input as $name => $value ) {
            $output [ is_int($name) || ctype_digit($name) ? self::sanitizeInt($name) : self::sanitizeName($name) ] = is_null($value) ? null : (is_array($value) ? $value : (string) $value);
        }
        // TODO: if ( !( is_string($value) || is_int($value) || is_null($value) ) ) throw new Exception("Invalid type");
        return $output;
    }

    public static function sanitizeFilters( array $input ) : array {
        $output = [];
        foreach ( $input as $name => $value ) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $output[] = is_null($item) ? null : (string) $item;
                }
            } else {
                $output[] = is_null($value) ? null : (string) $value;
            }
        }
        // TODO: if ( !( is_string($value) || is_int($value) || is_null($value) ) ) throw new Exception("Invalid type");
        return $output;
    }

    public static function sanitizeOutput( string $input ) : string {
        $input = ( stripos(PHP_SAPI, 'CGI') === 0 ? htmlentities( $input ) : $input );
        return $input;
    }

    public static function prepareWhere( array &$post, array $filters, string $prefix = '' ) : array {

        $values  = [];
        $prefix  = self::sanitizeName($prefix);

        foreach ( $filters as $key => $value ) {
            $opr = self::sanitizeOpr( preg_match( '/^.+\[([^\[\]]+)]$/iU', trim($key), $matches ) ? array_pop($matches) : '=' );
            $key = self::sanitizeName( preg_replace( '/\[([^\[\]]+)]/iU', '', $key) );

            if ( is_string($value) ) { $values []= str_replace( '*', $key, "`*` $opr ?" ); $post []= $value; }
            else foreach($value as $node) { $values []= str_replace( '*', $key, "`*` $opr ?" ); $post []= $node; }
        }

        $fields  = array_keys($filters);

        return array($fields, $values);

    }

    public static function preparePost( array &$post, array $data, string $prefix = '', string $opr = '=' ) {

        $prefix  = self::sanitizeName  ( $prefix );
        $data    = self::sanitizeArray ( $data );
        $isAssoc = count(array_filter(array_keys($data), 'is_string')) > 0;
        $isSeq   = array_keys($data) === range(0, count($data) - 1);

        if ( !empty($prefix) ) $prefix .= '_';
        if ( empty($post) ) $post = [];

        if ( $isAssoc ) {
            $fields  = array_keys($data);
            $pattern = ( empty($opr) ? ":$prefix*" : "`*` $opr :$prefix*" );
            $values  = self::array_set( $pattern, array_keys($data) );
            foreach ( $data as $key=>$value ) $post[$prefix.$key] = $value;
        } else
        if ( $isSeq ) {
            $fields  = array();
            $values  = array_fill( 0, count($data), '?' );
            $post    = array_values($data);
        } else {
            throw new \Exception("Database.php: Not supported yet!"); // TODO: for any array like array( 3=>'third row', 5=>'fifth row' )
        }

        /*
        // TODO: instead of NULL, use DEFAULT for this
        for ( $i=0; $i<count($data); $i++ )
            if ( $data[$i] === NULL) {
                unset($post[$i]);
                $values[$i] = 'NULL';
            }
        */

        return array($fields, $values);

    }

    public static function buildWhere( array $filters ) : string {
        $where = [];

        foreach( $filters as $key => $value ) {
            $key = self::sanitizeName( preg_replace( '/\[([^\[\]]+)]/iU', '', $key) );
            $opr = self::sanitizeOpr( preg_match( '/^.+\[([^\[\]]+)]$/iU', trim($key), $matches ) ? array_pop($matches) : (is_array($value) ? 'IN' : '=') );
            $sanitizedValue = is_array($value) ? ('(' . str_repeat('?,', count($value) - 1) . '?)') : '?';
            $where []= str_replace( '*', $key, "`*` $opr $sanitizedValue" );
        }

        return implode(' AND ', $where);
    }

    public static function formatSQL( string $sql, PDO $db ) : string {

        // Normalize line endings
        $sql = preg_replace( '|[\r\n]+|', "\n", $sql );

        // Remove any singe line comment
        //$sql = preg_replace( '~(?:\-{2}|\#{1})(?:[ \t]+[^\n]*)?$~iUm', '', $sql );

        // Remove any multiple lines comment
        //$sql = preg_replace( '|\/\*[\s\S]*\*\/|iU', '', $sql );

        // Trim any useless spaces
        $sql = trim( preg_replace( '|\s+|', ' ', $sql ) );

        // Replace any empty selection i.e. INSERT INTO `table_name` ()
        $sql = preg_replace( '|(`\w+`)\s*\(\s*\)|iU', '$1', $sql );

        // Remove any empty clause i.e. WHERE()
        $sql = preg_replace( '|\b[\w\s]+\b\s*\(\s*\)|iU', '', $sql );

        // If the LIMIT amount is set to INF, remove the clause
        $sql = preg_replace( '~\bLIMIT (INF|Infinity)\b~', '', $sql);

        // Remove additional white-spaces and keep only one semicolon
        $sql = trim( trim($sql), ';' ) . ';';

        // Check for server-specific corrections
        $dbDriver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Microsoft SQL Server based queries
        if ( in_array($dbDriver, ['sqlsrv', 'mssql', 'dblib']) ) {

            // Change the "`..`" format to "[...]" format
            $sql = preg_replace( '|\`([^\`]+)\`|iU', '[\1]', $sql );

            // Change "LIMIT n" to "TOP n" format
            $sql = preg_replace_callback( '@(?:^|;)(?<clause>\w+)\s+(?<parameters>[^\;]+)\s+LIMIT (?<limit>\w+)\;@iU',
                fn($section) => "${section['clause']} TOP ${section['limit']} ${section['parameters']}"
            , $sql );

            // Remove additional white-spaces and keep only one semicolon
            $sql = trim( trim($sql), ';' ) . ';';

        }

        return $sql;

    }

}
