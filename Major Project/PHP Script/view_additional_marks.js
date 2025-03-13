// view_additional_marks.js

$(document).ready(function() {
    // Load available semesters
    loadSemesters();

    // Semester Change Handler
    $('#semester').change(function() {
        if ($(this).val()) {
            loadAdditionalSubjects();
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
        loadAdditionalMarks();
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

// Load Additional Subjects based on semester
function loadAdditionalSubjects() {
    const semester = $('#semester').val();
    
    $.ajax({
        url: 'view_additional_marks_api.php',
        type: 'GET',
        data: {
            action: 'get_additional_subjects',
            semester: semester
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
                $('#subject').html('<option value="">No additional subjects available</option>');
                console.error('Error loading additional subjects:', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
        }
    });
}

// Load Additional Student Marks
function loadAdditionalMarks() {
    const semester = $('#semester').val();
    const subject = $('#subject').val();
    
    if (!semester || !subject) {
        alert('Please select both semester and subject');
        return;
    }
    
    $.ajax({
        url: 'view_additional_marks_api.php',
        type: 'GET',
        data: {
            action: 'get_marks',
            semester: semester,
            subject_code: subject
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.status === 'success') {
                displayAdditionalMarks(response);
                $('#marksDisplay').show();
                $('#semesterInfo').text('Semester ' + semester);
                $('#subjectInfo').text(response.subject_name);
            } else {
                $('#marksTable').html('');
                $('#noData').show();
                $('#marksDisplay').show();
                console.error('Error loading additional marks:', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
        }
    });
}

// Display Additional Marks in Table
function displayAdditionalMarks(data) {
    $('#noData').hide();
    
    let tableContent = '';
    let headerRow = '';
    const isProjectWork = data.subject_name.toLowerCase().includes('project work');
    const isMinorProject = data.subject_name.toLowerCase().includes('minor');
    const isMajorProject = data.subject_name.toLowerCase().includes('major');
    const isIndustrialExposure = data.subject_name.toLowerCase().includes('industrial exposure');
    const isGeneralProficiency = data.subject_name.toLowerCase().includes('general proficiency');
    const isIndustrialTraining = data.subject_name.toLowerCase().includes('industrial training');
    
    // Create table header based on subject type
    headerRow = '<thead><tr class="align-middle text-center">';
    headerRow += '<th rowspan="2">S.No</th>';
    headerRow += '<th rowspan="2">Roll No</th>';
    headerRow += '<th rowspan="2">Student Name</th>';
    
    if (isProjectWork) {
        // For project work subjects
        headerRow += '<th colspan="6" class="text-center project-header">Project Work Marks</th>';
    } else if (isIndustrialExposure || isGeneralProficiency) {
        // For Industrial Exposure or General Proficiency
        headerRow += '<th rowspan="2" class="text-center additional-header">Marks<span class="max-marks">(25)</span></th>';
    } else if (isIndustrialTraining) {
        // For Industrial Training
        headerRow += '<th rowspan="2" class="text-center additional-header">Marks<span class="max-marks">(30)</span></th>';
    } else {
        // For other additional subjects with single marks field
        headerRow += '<th rowspan="2" class="text-center additional-header">Marks</th>';
    }
    
    headerRow += '</tr>';
    
    if (isProjectWork) {
        headerRow += '<tr>';
        headerRow += '<th class="bg-light-purple">First Review<span class="max-marks">(10)</span></th>';
        headerRow += '<th class="bg-light-purple">Second Review<span class="max-marks">(10)</span></th>';
        headerRow += '<th class="bg-light-purple">Viva<span class="max-marks">(10)</span></th>';
        if (isMinorProject) {
            headerRow += '<th class="bg-light-purple">Practical Work<span class="max-marks">(10)</span></th>';
        } else if (isMajorProject) {
            headerRow += '<th class="bg-light-purple">Practical Work<span class="max-marks">(15)</span></th>';
        } else {
            headerRow += '<th class="bg-light-purple">Practical Work<span class="max-marks">(10)</span></th>';
        }
        headerRow += '<th class="bg-light-purple">Attendance<span class="max-marks">(10)</span></th>';
        // Add new Total header
        if (isMinorProject) {
            headerRow += '<th class="bg-light-purple">Total<span class="max-marks">(50)</span></th>';
        } else if (isMajorProject) {
            headerRow += '<th class="bg-light-purple">Total<span class="max-marks">(55)</span></th>';
        } else {
            headerRow += '<th class="bg-light-purple">Total<span class="max-marks">(50)</span></th>';
        }
        headerRow += '</tr>';
    }
    
    headerRow += '</thead>';
    
    tableContent += headerRow;
    tableContent += '<tbody>';
    
    // Add student data rows
    if (data.students && data.students.length > 0) {
        $.each(data.students, function(index, student) {
            tableContent += '<tr>';
            tableContent += `<td>${index + 1}</td>`;
            tableContent += `<td>${student.roll_no}</td>`;
            tableContent += `<td>${student.student_name}</td>`;
            
            if (isProjectWork) {
                // For project work subjects
                tableContent += `<td class="text-center">${student.first_review || '-'}</td>`;
                tableContent += `<td class="text-center">${student.second_review || '-'}</td>`;
                tableContent += `<td class="text-center">${student.viva || '-'}</td>`;
                tableContent += `<td class="text-center">${student.practical_work || '-'}</td>`;
                tableContent += `<td class="text-center">${student.attendance || '-'}</td>`;
                // Add Total column
                tableContent += `<td class="text-center">${student.total_marks || '-'}</td>`;
            } else {
                // For other additional subjects
                tableContent += `<td class="text-center">${student.marks || '-'}</td>`;
            }
            
            tableContent += '</tr>';
        });
    } else {
        const colSpan = isProjectWork ? 9 : 4;
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
                // The column index will depend on whether it's a project work or regular subject
                const isProjectWork = $('#marksTable thead tr th').length > 4;
                // If project work, use the total marks column (index 8) instead of practical work
                const marksIndex = isProjectWork ? 8 : 3; // Updated to use total_marks column
                
                aVal = parseFloat($(a).find(`td:eq(${marksIndex})`).text()) || 0;
                bVal = parseFloat($(b).find(`td:eq(${marksIndex})`).text()) || 0;
                return aVal - bVal;
            }
            
            case 'marks_desc': {
                // The column index will depend on whether it's a project work or regular subject
                const isProjectWork = $('#marksTable thead tr th').length > 4;
                // If project work, use the total marks column (index 8) instead of practical work
                const marksIndex = isProjectWork ? 8 : 3; // Updated to use total_marks column
                
                aVal = parseFloat($(a).find(`td:eq(${marksIndex})`).text()) || 0;
                bVal = parseFloat($(b).find(`td:eq(${marksIndex})`).text()) || 0;
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
    const isProjectWork = subjectInfo.toLowerCase().includes('project work');
    const marksTypeText = isProjectWork ? 'Project Work Marks' : 'Additional Subject Marks';
    
    // Add title
    doc.setFont("Poppins");
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Student Additional Marks Report', 40, 40);
    
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
            lineWidth: 0.5,
            lineColor: [0, 0, 0],
            fontSize: 10,
            cellPadding: 4,
            font: 'Poppins'
        },

        headStyles: {
            halign: 'center'
        },

        bodyStyles: {
            textColor: [0, 0, 0]
        },

        columnStyles: {
            0: {cellWidth: 'auto', lineWidth: 0.5},
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

// Export to CSV function
function exportToCSV() {
    const semesterInfo = $('#semesterInfo').text().replace(/\s/g, '_');
    const subjectInfo = $('#subjectInfo').text().replace(/\s/g, '_').replace(/[()]/g, '');
    const isProjectWork = subjectInfo.toLowerCase().includes('project work');
    
    // Prepare CSV headers
    let csvContent = "S.No,Roll No,Student Name";
    
    if (isProjectWork) {
        csvContent += ",First Review,Second Review,Viva,Practical Work,Attendance,Total";
    } else {
        csvContent += ",Marks";
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