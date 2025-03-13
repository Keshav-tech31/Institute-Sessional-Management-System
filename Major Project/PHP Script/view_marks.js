// view_marks.js

$(document).ready(function() {
    // Load available semesters
    loadSemesters();

    // Mark Type Change Handler
    $('input[name="marksType"]').change(function() {
        if ($('#semester').val()) {
            loadSubjects();
        }
    });

    // Semester Change Handler
    $('#semester').change(function() {
        if ($(this).val()) {
            loadSubjects();
            $('#subject').prop('disabled', false);
        } else {
            $('#subject').prop('disabled', true);
            $('#subject').html('<option value="">Select Subject</option>');
            $('#loadMarks').prop('disabled', true);
        }
    });

    // Subject Change Handler
    $('#subject').change(function() {
        if ($(this).val()) {
            $('#loadMarks').prop('disabled', false);
        } else {
            $('#loadMarks').prop('disabled', true);
        }
    });

    // Load Marks Button Click
    $('#loadMarks').click(function() {
        loadStudentMarks();
    });

    // Search Input Handler
    $('#searchInput').on('keyup', function() {
        filterTable();
    });

    // Sort Option Handler
    $('#sortOption').change(function() {
        sortTable($(this).val());
    });

    // Print Button Click
    $('#printMarks').click(function() {
        window.print();
    });

    // Export to PDF Button Click
    $('#exportPDF').click(function() {
        exportToPDF();
    });

    // Export to CSV Button Click
    $('#exportCSV').click(function() {
        exportToCSV();
    });
});

// Load Semesters
function loadSemesters() {
    $.ajax({
        url: 'get_semesters.php',
        type: 'GET',
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.status === 'success') {
                let options = '<option value="">Select Semester</option>';
                $.each(response.semesters, function(index, semester) {
                    options += `<option value="${semester.id}">${semester.name}</option>`;
                });
                $('#semester').html(options);
            } else {
                console.error('Error loading semesters:', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
        }
    });
}

// Load Subjects based on semester and marks type
function loadSubjects() {
    const semester = $('#semester').val();
    const marksType = $('input[name="marksType"]:checked').val();
    
    let type = 'all';
    if (marksType === 'theory') {
        type = 'THEORY';
    } else if (marksType === 'practical') {
        type = 'PRACTICAL';
    }
    
    $.ajax({
        url: 'view_marks_api.php',
        type: 'GET',
        data: {
            action: 'get_subjects',
            semester: semester,
            type: type
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.status === 'success') {
                let options = '<option value="">Select Subject</option>';
                $.each(response.subjects, function(index, subject) {
                    options += `<option value="${subject.subject_code}">${subject.subject_name} (${subject.subject_code})</option>`;
                });
                $('#subject').html(options);
            } else {
                $('#subject').html('<option value="">No subjects available</option>');
                console.error('Error loading subjects:', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
        }
    });
}

// Load Student Marks
function loadStudentMarks() {
    const semester = $('#semester').val();
    const subject = $('#subject').val();
    const marksType = $('input[name="marksType"]:checked').val();
    
    if (!semester || !subject) {
        alert('Please select both semester and subject');
        return;
    }
    
    $.ajax({
        url: 'view_marks_api.php',
        type: 'GET',
        data: {
            action: 'get_marks',
            semester: semester,
            subject_code: subject,
            marks_type: marksType
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.status === 'success') {
                displayMarks(response, marksType);
                $('#marksDisplay').show();
                $('#semesterInfo').text('Semester ' + semester);
                $('#subjectInfo').text(response.subject_name);
            } else {
                $('#marksTable').html('');
                $('#noData').show();
                $('#marksDisplay').show();
                console.error('Error loading marks:', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
        }
    });
}

// Display Marks in Table
function displayMarks(data, marksType) {
    $('#noData').hide();
    
    let tableContent = '';
    let headerRow = '';
    
    // Create table header based on marks type
    headerRow = '<thead><tr class="align-middle text-center">';
    headerRow += '<th rowspan="2">S.No</th>';
    headerRow += '<th rowspan="2">Roll No</th>';
    headerRow += '<th rowspan="2">Student Name</th>';
    
    if (marksType === 'theory' || marksType === 'both') {
        headerRow += '<th colspan="5" class="text-center marks-header">Theory Marks</th>';
    }
    
    if (marksType === 'practical' || marksType === 'both') {
        headerRow += '<th colspan="5" class="text-center practical-header">Practical Marks</th>';
    }
    
    headerRow += '</tr><tr>';
    
    if (marksType === 'theory' || marksType === 'both') {
        headerRow += '<th class="bg-light-blue">Class Test (30)</th>';
        headerRow += '<th class="bg-light-blue">Mid Term (50)</th>';
        headerRow += '<th class="bg-light-blue">Attendance (20)</th>';
        headerRow += '<th class="bg-light-blue">Total (100)</th>';
        headerRow += '<th class="bg-light-blue">Sessional (30)</th>';
    }
    
    if (marksType === 'practical' || marksType === 'both') {
        headerRow += '<th class="bg-light-green">Viva (30)</th>';
        headerRow += '<th class="bg-light-green">File (50)</th>';
        headerRow += '<th class="bg-light-green">Attendance (20)</th>';
        headerRow += '<th class="bg-light-green">Total (100)</th>';
        headerRow += '<th class="bg-light-green">Practical (30)</th>';
    }
    
    headerRow += '</tr></thead>';
    
    tableContent += headerRow;
    tableContent += '<tbody>';
    
    // Add student data rows
    if (data.students && data.students.length > 0) {
        $.each(data.students, function(index, student) {
            tableContent += '<tr>';
            tableContent += `<td>${index + 1}</td>`;
            tableContent += `<td>${student.roll_no}</td>`;
            tableContent += `<td>${student.student_name}</td>`;
            
            if (marksType === 'theory' || marksType === 'both') {
                tableContent += `<td class="text-center">${student.theory?.class_test || '-'}</td>`;
                tableContent += `<td class="text-center">${student.theory?.mid_term || '-'}</td>`;
                tableContent += `<td class="text-center">${student.theory?.attendance || '-'}</td>`;
                tableContent += `<td class="text-center">${student.theory?.total_marks || '-'}</td>`;
                tableContent += `<td class="text-center">${student.theory?.sessional || '-'}</td>`;
            }
            
            if (marksType === 'practical' || marksType === 'both') {
                tableContent += `<td class="text-center">${student.practical?.practical_viva || '-'}</td>`;
                tableContent += `<td class="text-center">${student.practical?.practical_file || '-'}</td>`;
                tableContent += `<td class="text-center">${student.practical?.attendance || '-'}</td>`;
                tableContent += `<td class="text-center">${student.practical?.total_marks || '-'}</td>`;
                tableContent += `<td class="text-center">${student.practical?.practical || '-'}</td>`;
            }
            
            tableContent += '</tr>';
        });
    } else {
        const colSpan = marksType === 'both' ? 13 : 8;
        tableContent += `<tr><td colspan="${colSpan}" class="text-center">No data available</td></tr>`;
    }
    
    tableContent += '</tbody>';
    
    $('#marksTable').html(tableContent);
    
    // Apply initial sort
    sortTable($('#sortOption').val());
}

// Filter table based on search input
function filterTable() {
    const searchText = $('#searchInput').val().toLowerCase();
    
    $("#marksTable tbody tr").each(function() {
        const studentName = $(this).find("td:eq(2)").text().toLowerCase();
        const rollNo = $(this).find("td:eq(1)").text().toLowerCase();
        
        if (studentName.includes(searchText) || rollNo.includes(searchText)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Sort table based on selected option
function sortTable(sortOption) {
    const tbody = $('#marksTable tbody');
    const rows = tbody.find('tr').toArray();
    
    rows.sort(function(a, b) {
        let aVal, bVal;
        
        switch(sortOption) {
            case 'roll_asc':
                aVal = $(a).find('td:eq(1)').text();
                bVal = $(b).find('td:eq(1)').text();
                return aVal.localeCompare(bVal, undefined, {numeric: true});
            
            case 'roll_desc':
                aVal = $(a).find('td:eq(1)').text();
                bVal = $(b).find('td:eq(1)').text();
                return bVal.localeCompare(aVal, undefined, {numeric: true});
            
            case 'name_asc':
                aVal = $(a).find('td:eq(2)').text();
                bVal = $(b).find('td:eq(2)').text();
                return aVal.localeCompare(bVal);
            
            case 'name_desc':
                aVal = $(a).find('td:eq(2)').text();
                bVal = $(b).find('td:eq(2)').text();
                return bVal.localeCompare(aVal);
            
            case 'marks_asc': {
                const marksType = $('input[name="marksType"]:checked').val();
                const totalIndex = marksType === 'practical' ? 6 : 
                                  (marksType === 'both' ? 6 : 4);
                
                aVal = parseFloat($(a).find(`td:eq(${totalIndex})`).text()) || 0;
                bVal = parseFloat($(b).find(`td:eq(${totalIndex})`).text()) || 0;
                return aVal - bVal;
            }
            
            case 'marks_desc': {
                const marksType = $('input[name="marksType"]:checked').val();
                const totalIndex = marksType === 'practical' ? 6 : 
                                  (marksType === 'both' ? 6 : 4);
                
                aVal = parseFloat($(a).find(`td:eq(${totalIndex})`).text()) || 0;
                bVal = parseFloat($(b).find(`td:eq(${totalIndex})`).text()) || 0;
                return bVal - aVal;
            }
            
            default:
                return 0;
        }
    });
    
    $.each(rows, function(index, row) {
        tbody.append(row);
        $(row).find('td:first').text(index + 1);
    });
}

// Export to PDF
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    
    // Get semester and subject information
    const semesterInfo = $('#semesterInfo').text();
    const subjectInfo = $('#subjectInfo').text();
    const marksType = $('input[name="marksType"]:checked').val();
    const marksTypeText = marksType === 'theory' ? 'Theory Marks' : 
                         (marksType === 'practical' ? 'Practical Marks' : 'Theory & Practical Marks');
    
    // Add title
    doc.setFont("Poppins");
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Student Marks Report', 40, 40);
    
    // Add semester and subject info
    doc.setFont("Poppins");
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text(`${semesterInfo} - ${subjectInfo} - ${marksTypeText}`, 40, 60);
    
    // Generate date
    doc.setFont("Poppins");
    doc.setFont(undefined, 'bold');
    const today = new Date();
    const dateStr = today.toLocaleDateString();
    doc.text(`Generated on: ${dateStr}`, 40, 80);
    
    // Create PDF table
    doc.autoTable({
        html: '#marksTable',
        startY: 100,
        styles: {
            lineWidth:0.5,
            lineColor:[0, 0, 0],
            fontSize: 10,
            cellPadding: 4,
            font: 'Poppins'
        },

        headStyles: {
            halign:'center'
        },

        bodyStyles: {
            textColor: [0, 0, 0]
        },

        columnStyles: {
            0: {cellWidth: 'auto', lineWidth:0.5},
            1: {lineWidth: 0.5},
            2: {lineWidth: 0.5}
        },
        didDrawPage: function(data) {
            // Footer with page number
            doc.setFontSize(10);
            doc.text(`Page ${doc.internal.getNumberOfPages()}`, data.settings.margin.right, doc.internal.pageSize.height - 10);
        }
    });
    
    // Save PDF
    const filename = `${semesterInfo}_${subjectInfo}_${marksTypeText.replace(/\s/g, '_')}.pdf`;
    doc.save(filename);
}

// Export to CSV function continuation
function exportToCSV() {
    const marksType = $('input[name="marksType"]:checked').val();
    const semesterInfo = $('#semesterInfo').text().replace(/\s/g, '_');
    const subjectInfo = $('#subjectInfo').text().replace(/\s/g, '_').replace(/[()]/g, '');
    
    // Prepare CSV headers
    let csvContent = "S.No,Roll No,Student Name";
    
    if (marksType === 'theory' || marksType === 'both') {
        csvContent += ",Class Test,Mid Term,Attendance,Total Marks,Sessional";
    }
    
    if (marksType === 'practical' || marksType === 'both') {
        csvContent += ",Practical Viva,Practical File,Attendance,Total Marks,Practical";
    }
    
    csvContent += "\n";
    
    // Add each row of data
    $('#marksTable tbody tr').each(function() {
        const row = [];
        $(this).find('td').each(function() {
            // Replace empty cells with empty string
            let cellText = $(this).text().trim();
            if (cellText === '-') cellText = '';
            
            // Wrap text with quotes to handle commas in text
            row.push(`"${cellText}"`);
        });
        csvContent += row.join(',') + "\n";
    });
    
    // Create download link
    const encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${semesterInfo}_${subjectInfo}_Marks.csv`);
    document.body.appendChild(link);
    
    // Download the CSV file
    link.click();
    document.body.removeChild(link);
}

// Show loading spinner
function showLoading() {
    $('.loading').css('display', 'flex');
}

// Hide loading spinner
function hideLoading() {
    $('.loading').css('display', 'none');
}