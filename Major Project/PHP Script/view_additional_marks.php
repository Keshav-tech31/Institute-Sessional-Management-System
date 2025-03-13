<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Core Marks</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>

        body {
            font-family: 'Poppins', sans-serif;
        }

        #marksTable {
            font-family: 'Poppins', sans-serif;
        }


        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .table-responsive {
            border-radius: 0 0 10px 10px;
        }
        .table {
            margin-bottom: 0;
        }
        .bg-light-blue {
            background-color: #f0f7ff;
        }
        .bg-light-green {
            background-color: #f0fff7;
        }
        .marks-header {
            background-color: #eef5ff;
            font-weight: bold;
        }
        .practical-header {
            background-color: #efffef;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
        .filter-container {
            padding: 15px;
            border-radius: 10px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container-fluid p-4">
        <div class="card">
            <div class="card-header py-3">
                <h5 class="m-0 font-weight-bold">View Additional Marks</h5>
            </div>
            <div class="card-body">

                <!-- Step 1: Semester & Subject Selection -->
                <div class="row mb-4 no-print">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold">Step 1: Select Semester & Subject</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="semester" class="form-label">Semester</label>
                                        <select class="form-select" id="semester">
                                            <option value="">Select Semester</option>
                                            <!-- Semesters will be loaded dynamically -->
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <select class="form-select" id="subject" disabled>
                                            <option value="">Select Subject</option>
                                            <!-- Subjects will be loaded dynamically -->
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <button id="loadMarks" class="btn btn-primary mt-2" disabled>
                                            <i class="fas fa-search me-2"></i>Load Marks
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Display Student Marks -->
                <div id="marksDisplay" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold">Student Marks</h6>
                                    <div id="marksInfo">
                                        <span class="badge bg-primary" id="semesterInfo"></span>
                                        <span class="badge bg-success" id="subjectInfo"></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Filtering and Sorting Options -->
                                    <div class="filter-container mb-3 no-print">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by Name or Roll No">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-sort"></i></span>
                                                    <select class="form-select" id="sortOption">
                                                        <option value="roll_asc">Roll No (Ascending)</option>
                                                        <option value="roll_desc">Roll No (Descending)</option>
                                                        <option value="name_asc">Name (A-Z)</option>
                                                        <option value="name_desc">Name (Z-A)</option>
                                                        <option value="marks_asc">Total Marks (Low-High)</option>
                                                        <option value="marks_desc">Total Marks (High-Low)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Marks Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="marksTable">
                                            <!-- Table content will be loaded dynamically -->
                                        </table>
                                    </div>

                                    <!-- No data message -->
                                    <div id="noData" class="no-data" style="display: none;">
                                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                                        <p>No marks data available for the selected criteria.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="row no-print">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold">Export Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-primary" id="printMarks">
                                            <i class="fas fa-print me-2"></i>Print
                                        </button>
                                        <button class="btn btn-success" id="exportPDF">
                                            <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                        </button>
                                        <button class="btn btn-info" id="exportCSV">
                                            <i class="fas fa-file-csv me-2"></i>Export to CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap JS and other libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src="view_additional_marks.js"></script>
</body>
</html>