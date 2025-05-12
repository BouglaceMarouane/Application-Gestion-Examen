<?php
    // This file is meant to be included in other scripts, not accessed directly
    if (!defined('INCLUDED_FILE')) {
        exit('Direct access not permitted');
    }

    // Ensure we have the student ID
    if (!isset($etudiant_id) || empty($etudiant_id)) {
        return false;
    }

    // Get student info
    $student = getStudentInfo($conn, $etudiant_id);

    if (!$student) {
        return false;
    }

    // Get student results
    $results = getStudentResults($conn, $etudiant_id);

    // Get student averages by subject
    $averages = getStudentAverageBySubject($conn, $etudiant_id);

    // Calculate overall average
    $overall_average = 0;
    $total_coef = 0;

    foreach ($averages as $avg) {
        $overall_average += $avg['moyenne'] * $avg['coefficient'];
        $total_coef += $avg['coefficient'];
    }

    $overall_average = $total_coef > 0 ? $overall_average / $total_coef : 0;

    // Get the current academic year
    $current_year = date('Y');
    $academic_year = (date('n') >= 9) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;

    // Create new PDF document
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo
            $image_file = K_PATH_IMAGES . 'logo.png';
            if (file_exists($image_file)) {
                $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Set font
            $this->SetFont('helvetica', 'B', 15);
            // Position at 15 mm from top
            $this->SetY(15);
            // Title
            $this->Cell(0, 10, 'Bulletin de Notes', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(15);
        }

        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('School Exams System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Bulletin de Notes - ' . $student['nom_complet']);
    $pdf->SetSubject('Bulletin de Notes');

    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set some language-dependent strings
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Student information
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Informations de l\'étudiant', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    $html = '
    <table cellspacing="0" cellpadding="5" border="0">
        <tr>
            <td width="30%"><strong>Nom complet:</strong></td>
            <td width="70%">' . htmlspecialchars($student['nom_complet']) . '</td>
        </tr>
        <tr>
            <td><strong>Date de naissance:</strong></td>
            <td>' . ($student['date_naissance'] ? date('d/m/Y', strtotime($student['date_naissance'])) : 'Non spécifiée') . '</td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td>' . htmlspecialchars($student['email']) . '</td>
        </tr>
        <tr>
            <td><strong>Classe:</strong></td>
            <td>' . htmlspecialchars($student['classe_nom']) . '</td>
        </tr>
        <tr>
            <td><strong>Filière:</strong></td>
            <td>' . htmlspecialchars($student['filiere_nom']) . '</td>
        </tr>
        <tr>
            <td><strong>Année académique:</strong></td>
            <td>' . $academic_year . '</td>
        </tr>
    </table>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Results by subject
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Moyennes par matière', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    if (count($averages) > 0) {
        $html = '
        <table cellspacing="0" cellpadding="5" border="1">
            <tr style="background-color:#f2f2f2;">
                <th width="40%"><strong>Matière</strong></th>
                <th width="20%"><strong>Coefficient</strong></th>
                <th width="20%"><strong>Moyenne</strong></th>
                <th width="20%"><strong>Nombre de notes</strong></th>
            </tr>
        ';
        
        foreach ($averages as $avg) {
            $color = $avg['moyenne'] >= 10 ? '#d4edda' : '#f8d7da';
            $html .= '
            <tr>
                <td>' . htmlspecialchars($avg['matiere']) . '</td>
                <td align="center">' . $avg['coefficient'] . '</td>
                <td align="center" style="background-color:' . $color . ';">' . number_format($avg['moyenne'], 2) . '/20</td>
                <td align="center">' . $avg['nb_notes'] . '</td>
            </tr>
            ';
        }
        
        $overall_color = $overall_average >= 10 ? '#d4edda' : '#f8d7da';
        $html .= '
            <tr style="background-color:#f2f2f2;">
                <td colspan="2"><strong>Moyenne générale</strong></td>
                <td align="center" style="background-color:' . $overall_color . ';"><strong>' . number_format($overall_average, 2) . '/20</strong></td>
                <td></td>
            </tr>
        </table>
        ';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    } else {
        $pdf->Cell(0, 10, 'Aucune moyenne disponible.', 0, 1, 'L');
    }

    // Detailed results
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Détail des notes', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    if (count($results) > 0) {
        $html = '
        <table cellspacing="0" cellpadding="5" border="1">
            <tr style="background-color:#f2f2f2;">
                <th width="25%"><strong>Matière</strong></th>
                <th width="25%"><strong>Examen</strong></th>
                <th width="15%"><strong>Type</strong></th>
                <th width="15%"><strong>Date</strong></th>
                <th width="20%"><strong>Note</strong></th>
            </tr>
        ';
        
        foreach ($results as $result) {
            $grade_percentage = ($result['note'] / $result['bareme']) * 100;
            $color = $grade_percentage >= 50 ? '#d4edda' : '#f8d7da';
            $html .= '
            <tr>
                <td>' . htmlspecialchars($result['matiere']) . '</td>
                <td>' . htmlspecialchars($result['exam_title']) . '</td>
                <td align="center">' . htmlspecialchars($result['type_examen']) . '</td>
                <td align="center">' . date('d/m/Y', strtotime($result['date_examen'])) . '</td>
                <td align="center" style="background-color:' . $color . ';">' . $result['note'] . '/' . $result['bareme'] . ' (' . number_format($grade_percentage, 0) . '%)</td>
            </tr>
            ';
            
            if (!empty($result['commentaire'])) {
                $html .= '
                <tr>
                    <td colspan="5" style="background-color:#f8f9fa;"><em>Commentaire: ' . htmlspecialchars($result['commentaire']) . '</em></td>
                </tr>
                ';
            }
        }
        
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    } else {
        $pdf->Cell(0, 10, 'Aucune note disponible.', 0, 1, 'L');
    }

    // Performance status
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Statut de performance', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    $status_text = '';
    $status_color = '';

    if ($overall_average >= 16) {
        $status_text = 'Excellent';
        $status_color = '#28a745';
    } elseif ($overall_average >= 14) {
        $status_text = 'Très Bien';
        $status_color = '#007bff';
    } elseif ($overall_average >= 12) {
        $status_text = 'Bien';
        $status_color = '#17a2b8';
    } elseif ($overall_average >= 10) {
        $status_text = 'Moyen';
        $status_color = '#ffc107';
    } elseif ($overall_average < 10) {
        $status_text = 'Insuffisant';
        $status_color = '#dc3545';
    }else {
        $status_text = 'Non défini';
        $status_color = '#6c757d';
    }

    $html = '
    <table cellspacing="0" cellpadding="5" border="1">
        <tr>
            <td width="30%"><strong>Statut:</strong></td>
            <td width="70%" style="background-color:' . $status_color . '; color: white;"><strong>' . $status_text . '</strong></td>
        </tr>
        <tr>
            <td><strong>Observations:</strong></td>
            <td>';

    if ($overall_average >= 16) {
        $html .= 'Félicitations! Résultats exceptionnels.';
    } elseif ($overall_average >= 14) {
        $html .= 'Très bons résultats. Continuez ainsi!';
    } elseif ($overall_average >= 12) {
        $html .= 'Bons résultats. Quelques améliorations possibles.';
    } elseif ($overall_average >= 10) {
        $html .= 'Résultats satisfaisants. Des efforts supplémentaires sont recommandés.';
    } elseif ($overall_average < 10) {
        $html .= 'Résultats insuffisants. Un travail supplémentaire est nécessaire.';
    }else {
        $html .= 'Aucune observation disponible.';
    }

    $html .= '</td>
        </tr>
    </table>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Signature section
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', '', 12);

    $html = '
    <table cellspacing="0" cellpadding="5" border="0">
        <tr>
            <td width="50%" align="center">Date: ' . date('d/m/Y') . '</td>
            <td width="50%" align="center">Signature du directeur</td>
        </tr>
        <tr>
            <td height="40"></td>
            <td></td>
        </tr>
    </table>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Output the PDF
    $pdf_name = 'bulletin_' . $etudiant_id . '_' . date('Ymd') . '.pdf';
    $pdf->Output($pdf_name, 'D'); // 'D' means download

    // Exit to prevent any additional output
    exit;
?>