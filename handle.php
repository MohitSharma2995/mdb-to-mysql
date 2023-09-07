<?php
/**
 * Increasing the upload size for input file
 */
ini_set('post_max_size', '200M');
ini_set('upload_max_filesize', '50M');
// Note : If this not working update in php.ini file

// Rename upload file if already exist with same name
function editfile_name($file, $rename = null, $add = null)
{
	// $add is add character/alphanumeric at the end of file except extention default is `(1)`
	$data['size'] = $data['filename'] = $data['extension'] = $data['rename'] = "Invalid file";
	if (is_file($file)) {
		$data['size'] = ceil(filesize($file) / 1024) . "KB";
		$data['file_fullname'] = $file;
		$file = explode("/", $file);
		$original_file = end($file); // get file origin name
		$data['filename'] = $original_file;
		$get_ext = explode(".", $original_file);
		if (count($get_ext) > 0) {
			foreach ($get_ext as $key => $value) {
				$keys[$value] = $key;
			}
			$max = max($keys);
			$data['extension'] = $get_ext[$max];
			if ($rename) { // new name of file
				$get_ext[$max - 1] = $rename;
			} else {
				if (!$add) {
					$get_ext[$max - 1] = $get_ext[$max - 1] . "(1)";
				} else {
					$get_ext[$max - 1] = $get_ext[$max - 1] . $add;
				}
			}
			$data['rename'] = implode(".", $get_ext);
			ksort($data);
			return $data;
		} else {
			return $data;
		}
	} else {
		return $data;
	}
}

require("connection.php");
if (isset($_POST)) {
	$ww = 0;
	$start = microtime(true);
	// Provide write permission to folder as well
	$save_path = getcwd() . "/input/"; 
	$file = $_FILES['file']['name'];
	$temp = $_FILES['file']['tmp_name'];
	$chunk_name = explode(".", $file);
	if ($chunk_name > 0) {
		if (end($chunk_name) != "mdb") {
			exit("Please upload mdb file.");
		}
	} else {
		exit("Please upload mdb file.");
	}
	if (file_exists($save_path . $file) == true) {
		$change_name = editfile_name($save_path . $file, null, mt_rand(1, 100));
		$final_file = $change_name['rename'];
	} else {
		$final_file = $file;
	}
	if (move_uploaded_file($temp, $save_path . $final_file) == false) {
		exit("Unable to Move file.");
	}

	$dbName = $save_path . $final_file; // connect with ms access file

	$db = odbc_connect("DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$dbName;", "", "");
	if (!$db) {
		exit("Unable to connect");
	}

	function get_tables_name()
	{
		global $db;
		$tablelist = $final_tables = array(); // show tables in file and filtered specific tables
		$table = odbc_tables($db);
		while (odbc_fetch_row($table)) {
			if (odbc_result($table, "TABLE_TYPE") == "TABLE")
				$tablelist[] = odbc_result($table, "TABLE_NAME");
			;
		}
		if (!empty($tablelist)) {
			foreach ($tablelist as $table_name) {
				$query = "select * from [" . $table_name . "]"; // filtered table names
				$run = odbc_exec($db, $query);
				$result = odbc_fetch_array($run);

				// Change column name according to your database
				// And hold the table and columns name
				if (isset($result['Forename']) || isset($result['Course Date']) || isset($result['Surname']) || isset($result['Certification Number']) || isset($result['Fname']) || isset($result['first_name'])) { //checking columns
					$final_tables['column'][] = $result;
					$final_tables['table'][] = $table_name;
				}
			}
		}
		return $final_tables;
	}
	function get_columns()
	{
		global $conn;
		$column = null;
		$final_tables = get_tables_name();

		$created = 1;
		if (!empty($final_tables)) { // check if any final table exists
			if (array_key_exists('column', $final_tables) && array_key_exists('table', $final_tables)) {
				for ($i = 0; $i < count($final_tables['column']); $i++) {
					$table_n = $final_tables['table'][$i];
					$table_n = str_replace(' ', '_', $table_n);
					$table_n = preg_replace('/[^A-Za-z0-9\_]/', '', $table_n);
					$all_tables[] = $table_n;
					$qu = "Show tables like '" . $table_n . "'";
					$run = mysqli_query($conn, $qu);
					if (mysqli_num_rows($run) > 0) {
						$run1 = mysqli_query($conn, "describe " . $table_n . "");
						while ($colm = mysqli_fetch_array($run1)) {
							$exist_col[$table_n][] = $colm['Field'];
						}
					} else {
						$exist_col[$table_n][] = "table_not_exits";
					}
					$create_table = "CREATE TABLE `" . $table_n . "` (_ID int AUTO_INCREMENT primary key ";
					$table = $final_tables['column'][$i];
					$keys = array_keys($final_tables['column'][$i]);
					for ($j = 0; $j < count($keys); $j++) {
						$new_col[$table_n][] = $keys[$j];
						$create_table .= ",`" . $keys[$j] . "` varchar(25)";
					}
					$create_table .= " );";
					$create_tab[] = $create_table;
					$column[$table_n] = $keys;
				}

			}
		}
		return array("exists" => $exist_col, "new" => $new_col, "new_table" => $create_tab, "table_name" => $all_tables, "column" => $column);
	}
	function create_colm_tab()
	{
		global $conn;
		$data = get_columns();
		$total_tab = count($data['table_name']);
		for ($i = 0; $i < $total_tab; $i++) {
			$tables = $data['table_name'][$i];
			$diff = array_diff($data['new'][$tables], $data['exists'][$tables]); // diff b/w exists and new column
			if (in_array("table_not_exits", $diff) || !empty($diff)) {
				for ($j = 0; $j < count($data['new_table']); $j++) {
					mysqli_query($conn, $data['new_table'][$i]);
				}
			}
			$diff = array_values($diff);
			$alter_tab = $alter_tab_ar = null;
			$exist_id_col = 0; //check if _ID column exists
			$count_new_col = count($diff);
			if ($count_new_col > 0) {
				$alter_tab = "ALTER TABLE `" . $tables . "`";
				for ($j = 0; $j < $count_new_col; $j++) {
					$result = mysqli_query($conn, "SHOW COLUMNS FROM `" . $tables . "` LIKE '" . $diff[$j] . "'");
					$exists = (mysqli_num_rows($result)) ? 1 : 0; // if column not exists
					if ($exists == 0) {
						$alter_tab_ar[] = " add column `" . $diff[$j] . "` varchar(25)";
					}
				}
				if (isset($alter_tab_ar)) {
					$alter_tab .= implode(",", $alter_tab_ar);
					if (strpos($alter_tab, "add column") == true) {
						mysqli_query($conn, $alter_tab);
					}
				}
			}
			$check_id = mysqli_query($conn, "SHOW COLUMNS FROM `" . $tables . "` LIKE '_ID' ");
			if (mysqli_num_rows($check_id) < 1) {
				$alter_tab1 = "ALTER TABLE `" . $tables . "`add column `_ID` int AUTO_INCREMENT PRIMARY KEY";
				mysqli_query($conn, $alter_tab1);
			}
		}
	}
	function fetch_data()
	{ // get ms access data
		global $db;
		$result = null;
		create_colm_tab(); // create missing table and column
		$all_ms_table = get_tables_name();
		$ms_table = $all_ms_table['table'];
		$total_tabl = count($ms_table);
		for ($i = 0; $i < $total_tabl; $i++) {
			$query = "select * from [" . $ms_table[$i] . "]";
			$run = odbc_exec($db, $query);
			while ($results = odbc_fetch_array($run)) {
				$result[$ms_table[$i]][] = $results;
			}
		}
		return $result;
	}
	// feed data
	global $conn;
	$insert = null;
	$counter = 0;
	$db_table = get_columns()['table_name']; // mysql database tables name

	$ms_table = get_tables_name()['table']; // ms access database table name
	$ms_data = fetch_data();
	$db_column = get_columns()['column'];

	foreach ($db_column as $key => $value) {
		$val = null;
		for ($i = 0; $i < count($value); $i++) {
			$val[] = "`" . $value[$i] . "`";
		}
		$insert[$key] = "INSERT INTO `" . $key . "` " . "(" . implode(",", $val) . ")";
	}
	// array=table_name =>rows(1,2,3 ....)=>(table_col=value)
	$abcd = 0;
	foreach ($ms_data as $tab => $ins_val) { // eliminate table name
		$vals1 = null;
		$length = $type = null;
		$tab = str_replace(' ', '_', $tab);
		$tab = preg_replace('/[^A-Za-z0-9\_]/', '', $tab);
		// taking much of time remove if after check start here
		$exist_data = mysqli_query($conn, "select * from " . $tab); // check existing data
		$exist_dat = $exist_dat1 = null;
		$existing = array();
		// insteed phone or email
		while ($datas = mysqli_fetch_assoc($exist_data)) {
			if (array_key_exists("_ID", $datas)) {
				unset($datas['_ID']);
			}
			$exist_dat1[] = $datas;
		}

		$con_ex = @count($exist_dat1);
		for ($o = 0; $o < $con_ex; $o++) {
			$old_data = null;
			foreach ($exist_dat1[$o] as $ac) {
				$old_data[] = '"' . $ac . '"';
			}
			$existing[] = implode(",", $old_data);
		}

		$cnt = count($ins_val);
		for ($i = 0; $i < $cnt; $i++) { // eliminate number of rows
			$vals = $a = null;
			foreach ($ins_val[$i] as $keys => $values) { // separete column name and value
				$vals[$keys] = '"' . $values . '"';
				$temps[$keys] = '"' . $values . '"';
				if (is_numeric($values)) { // getting colmn type
					$type[$keys][] = "integer";
				} elseif (is_string($values)) {
					$type[$keys][] = "string";
				} else {
					$type[$keys][] = "";
				}
				$length[$keys][] = strlen($values); // getting colmn value length
			}
			$final = implode(",", $vals); // put this line out of loop
			if (empty($existing)) {
				$vals1[] = "(" . $final . ")";
			} elseif (!in_array($final, $existing)) {
				$vals1[] = "(" . $final . ")";
			}
		}
		$col_and_type = null;
		foreach ($type as $colm => $col_ty) { // 
			$full = null;
			// $colm; // column name
			$name = array_filter($col_ty, function ($var) {
				return (!empty($var)) ? $var : null; });
			$name = array_values($name);
			if ($name) { // column type
				$full .= $name[0];
			} else {
				$full .= null;
			}
			$full .= "," . max($length[$colm]); // column (max) length
			$col_and_type['new'][$colm] = $full; // column name,type,length
		}
		$quer = mysqli_query($conn, "describe " . $tab);
		while ($run_q = mysqli_fetch_array($quer)) {
			$length = null;
			if ($run_q['Field'] != "_ID") {
				preg_match('!\d+!', $run_q['Type'], $matches);
				if (isset($matches[0])) {
					$length = $matches[0];
				}
				$col_and_type['old'][$run_q['Field']] = $run_q['Type'] . "," . $length;
			}
		}
		$old_count = $new_count = null;
		$new_count = count($col_and_type['new']); // new column
		$old_count = count($col_and_type['old']); // old column
		if ($new_count == $old_count) {
			foreach ($col_and_type['new'] as $x => $y) { // new column
				if (array_key_exists($x, $col_and_type['old'])) {
					$exp1 = explode(",", $y); // new column
					$exp2 = explode(",", $col_and_type['old'][$x]); // old column
					if ($exp1[1] > $exp2[1]) {
						$new = null;
						if ($exp1[0] == "integer" && $exp1[1] <= 11) {
							$new = " int ";
						} elseif ($exp1[0] == "string") {
							$new = " varchar(" . $exp1[1] . ")";
						}
						$ww++;
						mysqli_query($conn, "ALTER TABLE `" . $tab . "` CHANGE `" . $x . "` `" . $x . "` " . $new . " NULL DEFAULT NULL;");
					}
				} else {
					exit("Their is some error,while matching current database table column with new database.");
				}
			}
		} else {
			exit("Their is some error,while matching current database table column with new database.");
		}
		if (!empty($vals1)) {
			$combine = implode(",", $vals1);
			$finalssss = $insert[$tab] . " VALUES " . $combine . ";";
			$runq = mysqli_query($conn, $finalssss);
		}
	}
	echo "Record inserted successfully.";
} else {
	echo "Unable to insert record";
}
?>