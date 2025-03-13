document.addEventListener('DOMContentLoaded', function() {
    // Mock student data for testing - will be replaced with actual API calls
    const currentStudentId = 1; // This would come from session or URL parameter
    
    // Load student information
    loadStudentInfo(currentStudentId);
    
    // Set up tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all tabs
            tabBtns.forEach(b => b.classList.remove('active'));
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Set up semester selection
    const semesterSelect = document.getElementById('semester-select');
    semesterSelect.addEventListener('change', function() {
        const selectedSemester = this.value;
        loadSemesterData(currentStudentId, selectedSemester);
    });
    
    // Initial load of available semesters
    loadAvailableSemesters(currentStudentId);
});

// Function to load student basic information
function loadStudentInfo(studentId) {
    // This would be replaced with an actual API call
    fetch('student_api.php?action=get_student_info&student_id=' + studentId)
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('student-name').textContent = data.student.name;
                document.getElementById('roll-no').textContent = data.student.roll_no;
                document.getElementById('semester').textContent = data.student.current_semester;
            } else {
                showError('Failed to load student information');
            }
        })
        .catch(error => {
            console.error('Error loading student info:', error);
            showError('Error connecting to server');
        });
}

// Function to load available semesters for the student
function loadAvailableSemesters(studentId) {
    fetch('student_api.php?action=get_student_semesters&student_id=' + studentId)
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                const semesterSelect = document.getElementById('semester-select');
                semesterSelect.innerHTML = ''; // Clear existing options
                
                data.semesters.forEach(semester => {
                    const option = document.createElement('option');
                    option.value = semester.value;
                    option.textContent = semester.label;
                    semesterSelect.appendChild(option);
                });
                
                // Load data for the first semester by default
                if(data.semesters.length > 0) {
                    loadSemesterData(studentId, data.semesters[0].value);
                }
            } else {
                showError('Failed to load semester information');
            }
        })
        .catch(error => {
            console.error('Error loading semesters:', error);
            showError('Error connecting to server');
        });
}

// Function to load semester data (marks)
function loadSemesterData(studentId, semester) {
    // Update the semester display
    document.getElementById('semester').textContent = semester;
    
    // Load theory marks
    loadTheoryMarks(studentId, semester);
    
    // Load practical marks
    loadPracticalMarks(studentId, semester);
    
    // Load projects
    loadProjectMarks(studentId, semester);
    
    // Load other assessments
    loadOtherAssessments(studentId, semester);
}

// Function to load theory marks
function loadTheoryMarks(studentId, semester) {
    fetch(`student_api.php?action=get_theory_marks&student_id=${studentId}&semester=${semester}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('theory-marks-body');
            tableBody.innerHTML = ''; // Clear existing rows
            
            if(data.status === 'success' && data.marks.length > 0) {
                data.marks.forEach(mark => {
                    const row = document.createElement('tr');
                    
                    // Calculate classes based on marks
                    const classTestClass = getCellClass(mark.class_test, 30);
                    const midTermClass = getCellClass(mark.mid_term, 50);
                    const attendanceClass = getCellClass(mark.attendance, 20);
                    const totalClass = getCellClass(mark.total_marks, 100);
                    const sessionalClass = getCellClass(mark.sessional, 30);
                    
                    row.innerHTML = `
                        <td>${mark.subject_code}</td>
                        <td>${mark.subject_name}</td>
                        <td class="${classTestClass}">${mark.class_test !== null ? mark.class_test : '-'}</td>
                        <td class="${midTermClass}">${mark.mid_term !== null ? mark.mid_term : '-'}</td>
                        <td class="${attendanceClass}">${mark.attendance !== null ? mark.attendance : '-'}</td>
                        <td class="${totalClass}">${mark.total_marks !== null ? mark.total_marks : '-'}</td>
                        <td class="${sessionalClass}">${mark.sessional !== null ? mark.sessional : '-'}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-data">No theory marks available for this semester</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading theory marks:', error);
            document.getElementById('theory-marks-body').innerHTML = 
                '<tr><td colspan="7" class="error">Failed to load marks data. Please try again later.</td></tr>';
        });
}

// Function to load practical marks
function loadPracticalMarks(studentId, semester) {
    fetch(`student_api.php?action=get_practical_marks&student_id=${studentId}&semester=${semester}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('practical-marks-body');
            tableBody.innerHTML = ''; // Clear existing rows
            
            if(data.status === 'success' && data.marks.length > 0) {
                data.marks.forEach(mark => {
                    const row = document.createElement('tr');
                    
                    // Calculate classes based on marks
                    const vivaClass = getCellClass(mark.practical_viva, 30);
                    const fileClass = getCellClass(mark.practical_file, 50);
                    const attendanceClass = getCellClass(mark.attendance, 20);
                    const totalClass = getCellClass(mark.total_marks, 100);
                    const practicalClass = getCellClass(mark.practical, 30);
                    
                    row.innerHTML = `
                        <td>${mark.subject_code}</td>
                        <td>${mark.subject_name}</td>
                        <td class="${vivaClass}">${mark.practical_viva !== null ? mark.practical_viva : '-'}</td>
                        <td class="${fileClass}">${mark.practical_file !== null ? mark.practical_file : '-'}</td>
                        <td class="${attendanceClass}">${mark.attendance !== null ? mark.attendance : '-'}</td>
                        <td class="${totalClass}">${mark.total_marks !== null ? mark.total_marks : '-'}</td>
                        <td class="${practicalClass}">${mark.practical !== null ? mark.practical : '-'}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-data">No practical marks available for this semester</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading practical marks:', error);
            document.getElementById('practical-marks-body').innerHTML = 
                '<tr><td colspan="7" class="error">Failed to load marks data. Please try again later.</td></tr>';
        });
}

// Function to load project marks
function loadProjectMarks(studentId, semester) {
    // Load minor project marks
    fetch(`student_api.php?action=get_project_marks&student_id=${studentId}&semester=${semester}&type=minor`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('minor-project-body');
            tableBody.innerHTML = ''; // Clear existing rows
            
            if(data.status === 'success' && data.marks.length > 0) {
                data.marks.forEach(mark => {
                    const row = document.createElement('tr');
                    
                    // Calculate classes based on marks
                    const review1Class = getCellClass(mark.first_review, 10);
                    const review2Class = getCellClass(mark.second_review, 10);
                    const vivaClass = getCellClass(mark.viva, 10);
                    const practicalClass = getCellClass(mark.practical_work, 10);
                    const attendanceClass = getCellClass(mark.attendance, 20);
                    const totalClass = getCellClass(mark.total_marks, 50);
                    
                    row.innerHTML = `
                        <td>${mark.subject_code}</td>
                        <td>${mark.subject_name}</td>
                        <td class="${review1Class}">${mark.first_review !== null ? mark.first_review : '-'}</td>
                        <td class="${review2Class}">${mark.second_review !== null ? mark.second_review : '-'}</td>
                        <td class="${vivaClass}">${mark.viva !== null ? mark.viva : '-'}</td>
                        <td class="${practicalClass}">${mark.practical_work !== null ? mark.practical_work : '-'}</td>
                        <td class="${attendanceClass}">${mark.attendance !== null ? mark.attendance : '-'}</td>
                        <td class="${totalClass}">${mark.total_marks !== null ? mark.total_marks : '-'}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="no-data">No minor project data available for this semester</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading minor project data:', error);
            document.getElementById('minor-project-body').innerHTML = 
                '<tr><td colspan="8" class="error">Failed to load project data. Please try again later.</td></tr>';
        });
    
    // Load major project marks
    fetch(`student_api.php?action=get_project_marks&student_id=${studentId}&semester=${semester}&type=major`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('major-project-body');
            tableBody.innerHTML = ''; // Clear existing rows
            
            if(data.status === 'success' && data.marks.length > 0) {
                data.marks.forEach(mark => {
                    const row = document.createElement('tr');
                    
                    // Calculate classes based on marks
                    const review1Class = getCellClass(mark.first_review, 10);
                    const review2Class = getCellClass(mark.second_review, 10);
                    const vivaClass = getCellClass(mark.viva, 10);
                    const practicalClass = getCellClass(mark.practical_work, 15);
                    const attendanceClass = getCellClass(mark.attendance, 20);
                    const totalClass = getCellClass(mark.total_marks, 55);
                    
                    row.innerHTML = `
                        <td>${mark.subject_code}</td>
                        <td>${mark.subject_name}</td>
                        <td class="${review1Class}">${mark.first_review !== null ? mark.first_review : '-'}</td>
                        <td class="${review2Class}">${mark.second_review !== null ? mark.second_review : '-'}</td>
                        <td class="${vivaClass}">${mark.viva !== null ? mark.viva : '-'}</td>
                        <td class="${practicalClass}">${mark.practical_work !== null ? mark.practical_work : '-'}</td>
                        <td class="${attendanceClass}">${mark.attendance !== null ? mark.attendance : '-'}</td>
                        <td class="${totalClass}">${mark.total_marks !== null ? mark.total_marks : '-'}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="no-data">No major project data available for this semester</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading major project data:', error);
            document.getElementById('major-project-body').innerHTML = 
                '<tr><td colspan="8" class="error">Failed to load project data. Please try again later.</td></tr>';
        });
}

// Function to load other assessments
function loadOtherAssessments(studentId, semester) {
    // Load industrial exposure
    loadOtherAssessmentType(studentId, semester, 'industrial_exposure', 'industrial-exposure-body');
    
    // Load general proficiency
    loadOtherAssessmentType(studentId, semester, 'general_proficiency', 'general-proficiency-body');
    
    // Load industrial training
    loadOtherAssessmentType(studentId, semester, 'industrial_training', 'industrial-training-body');
}

// Helper function to load a specific type of other assessment
function loadOtherAssessmentType(studentId, semester, type, tableBodyId) {
    fetch(`student_api.php?action=get_other_assessment&student_id=${studentId}&semester=${semester}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById(tableBodyId);
            tableBody.innerHTML = ''; // Clear existing rows
            
            if(data.status === 'success' && data.marks.length > 0) {
                data.marks.forEach(mark => {
                    const row = document.createElement('tr');
                    
                    // Calculate class based on marks and max marks
                    let maxMarks;
                    if (type === 'industrial_training') {
                        maxMarks = 30;
                    } else {
                        maxMarks = 25;
                    }
                    const marksClass = getCellClass(mark.marks, maxMarks);
                    
                    row.innerHTML = `
                        <td>${mark.subject_code}</td>
                        <td>${mark.subject_name}</td>
                        <td class="${marksClass}">${mark.marks !== null ? mark.marks : '-'}</td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = `<tr><td colspan="3" class="no-data">No ${type.replace('_', ' ')} data available for this semester</td></tr>`;
            }
        })
        .catch(error => {
            console.error(`Error loading ${type} data:`, error);
            document.getElementById(tableBodyId).innerHTML = 
                `<tr><td colspan="3" class="error">Failed to load ${type.replace('_', ' ')} data. Please try again later.</td></tr>`;
        });
}

// Helper function to determine cell class based on marks
function getCellClass(marks, maxMarks) {
    if (marks === null) return '';
    
    // Calculate percentage of maximum marks
    const percentage = (marks / maxMarks) * 100;
    
    // Assign class based on percentage
    if (percentage >= 75) {
        return 'excellent'; // 75% or above
    } else if (percentage >= 60) {
        return 'good'; // 60% to 74%
    } else if (percentage >= 50) {
        return 'average'; // 50% to 59%
    } else if (percentage >= 40) {
        return 'pass'; // 40% to 49%
    } else {
        return 'fail'; // Below 40%
    }
}

// Function to show error messages
function showError(message) {
    // Create error alert
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.role = 'alert';
    errorDiv.innerHTML = `<strong>Error:</strong> ${message}`;
    
    // Add to page
    const mainContent = document.querySelector('.main-content');
    mainContent.insertBefore(errorDiv, mainContent.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Function to show success messages
function showSuccess(message) {
    // Create success alert
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success';
    successDiv.role = 'alert';
    successDiv.innerHTML = `<strong>Success:</strong> ${message}`;
    
    // Add to page
    const mainContent = document.querySelector('.main-content');
    mainContent.insertBefore(successDiv, mainContent.firstChild);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Export PDF function
document.getElementById('export-pdf').addEventListener('click', function() {
    const studentName = document.getElementById('student-name').textContent;
    const semester = document.getElementById('semester').textContent;
    const rollNo = document.getElementById('roll-no').textContent;
    
    // Create a title for the PDF
    const title = `${studentName} - Semester ${semester} - Marks Report`;
    
    // Get the active tab to export
    const activeTab = document.querySelector('.tab-pane.active');
    
    // Set up options for PDF generation
    const options = {
        margin: 10,
        filename: `${studentName}_Sem${semester}_Marks.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Create a wrapper div for the content
    const wrapper = document.createElement('div');
    wrapper.style.padding = '20px';
    
    // Add header
    const header = document.createElement('div');
    header.style.textAlign = 'center';
    header.style.marginBottom = '20px';
    header.innerHTML = `
        <h2>${title}</h2>
        <p><strong>Roll No:</strong> ${rollNo}</p>
    `;
    wrapper.appendChild(header);
    
    // Add the active tab content
    const content = activeTab.cloneNode(true);
    wrapper.appendChild(content);
    
    // Generate PDF
    html2pdf().from(wrapper).set(options).save();
});

// Function to print current view
document.getElementById('print-view').addEventListener('click', function() {
    window.print();
});