<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ensajconf";
try {
  // Création de la connexion à la base de données
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Vérification de la connexion
  if ($conn->connect_error) {
    throw new Exception("La connexion a échoué : " . $conn->connect_error);
  }
  // Requête SQL pour extraire les données de la table "conference"
  $sql = "SELECT id, title, `start_date`, `end_date`,`url` FROM conferences ORDER BY 
  title DESC";
  // Exécution de la requête SQL
  $result = $conn->query($sql);
  // Création du tableau HTML
 // Inclusion des fichiers DataTable
// Inclusion de la bibliothèque jQuery
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>";
// Inclusion des fichiers DataTable
echo "<link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/1.10.24/css/jquery.
dataTables.css'>";
echo "<script type='text/javascript' charset='utf8' src='https://cdn.datatables.net
/1.10.24/js/jquery.dataTables.js'></script>";
// Affichage du tableau avec DataTable
echo "<table id='myTable'>";
echo "<thead><tr> <th>ID</th> <th>Conference</th> <th>Start Date</th> <th>End Date</th> 
<th>Delete</th> <th>PDF</th> </tr></thead>";
echo "<tbody>";
// Parcours des résultats de la requête SQL
while($row = $result->fetch_assoc()) {
  echo "<tr>";
  echo "<td style='text-align: center;'>" . $row["id"] . "</td>";
  echo "<td style='text-align: center;'>" ;
  echo "<a href=\"" .  $row["url"]  . "\"target=\"_blank\">". $row["title"] ."</a>";
  echo  "</td>";
  echo "<td style='text-align: center;'>" . $row["start_date"] . "</td>";
  echo "<td style='text-align: center;'>" . $row["end_date"] . "</td>";
  echo'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/
  5.15.3/css/all.min.css">';
  echo "<td style='text-align: center;'><button style='background-color: #ff0000; 
  border-radius: 5px; padding: 5px 10px;' onclick='showConfirmDialog(" . $row["id"] . ")'>
  <i class='fas fa-trash-alt text-danger'></i></button></td>";
  echo "<td style='text-align: center;'><button style='background-color: #008CBA; border-radius: 5px; 
  padding: 5px 10px;'  onclick='generatePDF(\"" . $row["title"] . "\",\"" . $row["start_date"] . "\",\"" . 
  $row["end_date"] . "\",\"" . $row["url"] . "\")'>
  <i class='fas fa-download text-white'></i>
 </button></td>";
  echo "</tr>";
}
echo "</tbody>";
echo "</table>";
// Script DataTable
echo "<script>
$(document).ready( function () {
    $('#myTable').DataTable();
} );
</script>";
  //Suppression d'un enregistrement
  if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $sql = "DELETE FROM conferences WHERE id = $id";
    // Récupération du nom de la conférence
    $sql_conf = "SELECT title FROM conferences WHERE id = $id";
    $result_conf = $conn->query($sql_conf);
    if ($result_conf->num_rows > 0) {
      $row_conf = $result_conf->fetch_assoc();
      $conf_name = $row_conf["title"];
 // Suppression de dossier
 //$chemin_dossier = 'C:/xampp/htdocs/' . $conf_name;
 $directory = 'C:/xampp/htdocs/' . $conf_name; 
// Define a function to delete a directory and its contents recursively
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}
// Modify the permissions of the directory and all its contents
chmod($directory, 0777);
$objects = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST
);
foreach ($objects as $name => $object) {
    chmod($name, 0777);
}
// Call the delete_directory() function to delete the directory and its contents
if (delete_directory($directory)) {
    if (!file_exists($directory)) {
        //echo 'The directory has been deleted successfully';
    } else {
        echo 'There was an error deleting the directory';
    }
} else {
    echo 'There was an error deleting the directory';
}
      // Suppression de la base de données correspondante
      $sql_drop = "DROP DATABASE `$conf_name`";
      if ($conn->query($sql_drop) === FALSE) {
        echo "Demande en cours de traitement ... ";
      }  
      // Suppression de l'enregistrement dans la table "conferences"
      if ($conn->query($sql) === TRUE) {
       header("Refresh:0");
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/
        sweetalert2.min.js"></script>';
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2
        @11.0.19/dist/sweetalert2.min.css">';
        echo '<script>';
        echo 'Swal.fire({';
        echo '  icon: "success",';
        echo '  title: "Succès !",';
        echo '  text: "La conférence a été supprimée avec succès",';
        echo '  showConfirmButton: false';
        echo '});';
        echo '</script>';      
      } else {
        echo "Erreur lors de la suppression : " . $conn->error;
      } 
    } 
  }
  // Fermeture de la connexion à la base de données
  $conn->close();
} catch (Exception $e) {
  echo $e->getMessage();
}
?>

<div id="confirm-dialog" style="display:none; position:absolute; top:50%; 
left:50%;transform: translate(-50%, -50%); background-color:#fff; padding: 10px; 
border-radius: 5px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); width: 80%; text-align: center;
 font-size: 24px;">
    <p>Êtes-vous sûr(e) de vouloir supprimer cette conférence ?</p>
    <button style='background-color: #ff0000; border-radius: 5px; padding: 5px 10px; 
    margin-right: 10px;' onclick='deleteConference()'>Oui</button>
    <button style='background-color: #008000; border-radius: 5px; padding: 5px 10px;
    ' onclick='hideConfirmDialog()'>Non</button>
</div>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.min.js"></script>
<script>
function showConfirmDialog(id) {
    swal({
        title: "Are you sure you want to delete this conference ?",
        text: "This action is irreversible !",
        icon: "warning",
        buttons: ["No", "Yes"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            location.href = "index.php?id=" + id;
        } else {
            swal("Deletion has been undone !");
        }
    });
}
//generation des pdfs
function generatePDF(title, start, end, link) {
    var doc = new jsPDF();
    doc.text("Title     : " + title , 20, 20);
    doc.text("Start Date: " + start , 20, 30);
    doc.text("End Date  : " + end , 20, 40);
    doc.text("Link      : " + link , 20, 50); 
    doc.save(title+".pdf");
}
</script>