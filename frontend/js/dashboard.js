$(document).ready(function () {
    const API_BASE = '/assignment-uploader/backend/api';
    const userRole = localStorage.getItem('userRole');

    checkUserAuth();

    applyRoleUI(userRole);

    ensureGradeModal(API_BASE);

    loadDashboardData(API_BASE);

    $('.menu-item').on('click', function (e) {
        e.preventDefault();

        $('.menu-item').removeClass('active');

        $(this).addClass('active');

        const section = $(this).data('section');

        $('.content-section').removeClass('active');

        $(`#${section}`).addClass('active');

        loadSectionData(section, API_BASE);
    });

    $('#logoutBtn').on('click', function (e) {
        e.preventDefault();

        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userRole');
            localStorage.removeItem('token');
            window.location.href = 'login.html';
        }
    });

    $('.nav-link').on('click', function () {
        $('.sidebar').removeClass('open');
    });
});

function checkUserAuth() {
    const userEmail = localStorage.getItem('userEmail');
    const userRole = localStorage.getItem('userRole');
    const token = localStorage.getItem('token');

    if (!userEmail || !userRole || !token) {
        window.location.href = 'login.html';
        return;
    }

    $('#userEmail').text(userEmail.split('@')[0]);
}

function applyRoleUI(userRole) {
    if (userRole === 'faculty') {
        $('#submitted').closest('.stat-content').find('h4').text('Total Submissions');
        $('#pending').closest('.stat-content').find('h4').text('Pending Grading');
        $('#avgGrade').closest('.stat-content').find('h4').text('Graded');

        $('.menu-item[data-section="assignments"]').text('📝 My Assignments');
        $('.menu-item[data-section="submissions"]').text('📤 Student Submissions');
        $('.menu-item[data-section="grades"]').text('⭐ Given Grades');
        $('.menu-item[data-section="upload"]').text('➕ Create Assignment');

        $('#assignments h2').text('My Assignments');
        $('#submissions h2').text('Student Submissions');
        $('#grades h2').text('Given Grades');
        $('#upload h2').text('Create Assignment');
    }
}

function loadDashboardData(apiBase) {
    const userRole = localStorage.getItem('userRole');

    
    $.ajax({
        url: `${apiBase}/dashboard.php`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const data = response.data;

                
                $('#totalAssignments').text(data.totalAssignments || 0);
                if (userRole === 'faculty') {
                    $('#submitted').text(data.totalSubmissions || 0);
                    $('#pending').text(data.pendingGrading || 0);
                    $('#avgGrade').text(data.graded || 0);
                } else {
                    $('#submitted').text(data.submitted || 0);
                    $('#pending').text(data.pending || 0);
                    $('#avgGrade').text(data.avgGrade ? data.avgGrade.toFixed(2) : '--');
                }
            } else {
                console.error(response.message || 'Failed to load dashboard data');
            }
        },
        error: function (xhr) {
            if (xhr && xhr.status === 401) {
                localStorage.removeItem('userEmail');
                localStorage.removeItem('userRole');
                localStorage.removeItem('token');
                window.location.href = 'login.html';
                return;
            }
            console.error('Failed to load dashboard data');
        }
    });
}

function loadSectionData(section, apiBase) {
    const userRole = localStorage.getItem('userRole');
    const token = localStorage.getItem('token');

    switch (section) {
        case 'assignments':
            loadAssignments(apiBase);
            break;
        case 'submissions':
            loadSubmissions(apiBase);
            break;
        case 'grades':
            loadGrades(apiBase);
            break;
        case 'upload':
            setupUploadArea(apiBase);
            break;
        default:
            break;
    }
}

function loadAssignments(apiBase) {
    const userRole = localStorage.getItem('userRole');
    const container = $('#assignmentsList');
    container.html('<div class="spinner"></div>');

    $.ajax({
        url: `${apiBase}/assignments.php`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(function (assignment) {
                    const deadline = new Date(assignment.deadline);
                    const now = new Date();
                    const isOverdue = deadline < now;

                    const fileLink = assignment.filePath
                        ? `<div class="mt-2"><a href="../${assignment.filePath}" target="_blank" class="text-primary" style="text-decoration:none;">📎 Download: ${assignment.fileName}</a></div>`
                        : '';

                    html += `
                        <div class="assignment-card">
                            <h3>${assignment.title}</h3>
                            <p>${assignment.description}</p>
                            <div class="assignment-meta">
                                <span class="meta-item">📅 Due: ${formatDate(assignment.deadline)}</span>
                                <span class="meta-item">👥 Subject: ${assignment.subject}</span>
                                ${isOverdue ? '<span class="badge badge-danger">Overdue</span>' : '<span class="badge badge-warning">Active</span>'}
                            </div>
                            ${fileLink}
                        </div>
                    `;
                });
                container.html(html);
            } else {
                if (userRole === 'faculty') {
                    container.html('<p class="text-muted text-center p-4">No assignments created yet. Go to Create Assignment to add one.</p>');
                } else {
                    container.html('<p class="text-muted text-center p-4">No assignments available</p>');
                }
            }
        },
        error: function () {
            container.html('<p class="text-danger text-center p-4">Failed to load assignments</p>');
        }
    });
}

function loadSubmissions(apiBase) {
    const userRole = localStorage.getItem('userRole');
    const container = $('#submissionsList');
    container.html('<div class="spinner"></div>');

    $.ajax({
        url: `${apiBase}/submissions.php`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(function (submission) {
                    const statusBadge = submission.status === 'submitted'
                        ? '<span class="badge badge-success">Submitted</span>'
                        : '<span class="badge badge-warning">Pending Review</span>';
                    const plagiarismStatus = submission.plagiarismStatus || 'pending';
                    const plagiarismScore = submission.plagiarismScore !== null && submission.plagiarismScore !== undefined
                        ? Number(submission.plagiarismScore).toFixed(2)
                        : '0.00';
                    const plagiarismBadge = plagiarismStatus === 'suspected'
                        ? '<span class="badge badge-danger">Plagiarism Suspected</span>'
                        : (plagiarismStatus === 'clean'
                            ? '<span class="badge badge-success">Plagiarism: Clean</span>'
                            : '<span class="badge badge-warning">Plagiarism: Pending</span>');
                    const studentInfo = (userRole === 'faculty' && submission.firstName)
                        ? `<span class="meta-item">👤 Student: ${submission.firstName} ${submission.lastName}</span>`
                        : '';
                    const gradeButton = (userRole === 'faculty')
                        ? `<button type="button" class="btn btn-primary mt-2 grade-btn" data-submission-id="${submission.id}" data-assignment-title="${submission.assignmentTitle}">Grade Submission</button>`
                        : '';

                    html += `
                        <div class="submission-card">
                            <h3>${submission.assignmentTitle}</h3>
                            <div class="assignment-meta">
                                <span class="meta-item">📁 File: ${submission.fileName}</span>
                                <span class="meta-item">📅 Submitted: ${formatDate(submission.submittedDate)}</span>
                                <span class="meta-item">🧪 Similarity: <strong>${plagiarismScore}%</strong></span>
                                ${studentInfo}
                                ${statusBadge}
                                ${plagiarismBadge}
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                ${gradeButton}
                                ${userRole === 'faculty' && submission.plagiarismStatus === 'suspected'
                            ? `<button type="button" class="btn btn-warning view-report-btn" data-id="${submission.id}">🔍 View Plagiarism Report</button>`
                            : ''}
                            </div>
                        </div>
                    `;
                });
                container.html(html);

                if (userRole === 'faculty') {
                    $('.grade-btn').on('click', function () {
                        const submissionId = $(this).data('submission-id');
                        const assignmentTitle = $(this).data('assignment-title');
                        openGradeModal(submissionId, assignmentTitle);
                    });

                    $('.view-report-btn').on('click', function () {
                        const id = $(this).data('id');
                        openPlagiarismReport(id, apiBase);
                    });
                }
            } else {
                if (userRole === 'faculty') {
                    container.html('<p class="text-muted text-center p-4">No student submissions yet for your assignments.</p>');
                } else {
                    container.html('<p class="text-muted text-center p-4">No submissions yet</p>');
                }
            }
        },
        error: function () {
            container.html('<p class="text-danger text-center p-4">Failed to load submissions</p>');
        }
    });
}

function ensureGradeModal(apiBase) {
    if ($('#gradeModal').length) {
        return;
    }

    const modalHTML = `
        <div id="gradeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1100; align-items:center; justify-content:center; padding:1rem;">
            <div style="width:100%; max-width:520px; background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 12px 30px rgba(0,0,0,0.2);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
                    <h3 id="gradeModalTitle" style="margin:0; font-size:1.1rem;">Grade Submission</h3>
                    <button type="button" id="gradeModalClose" class="btn" style="padding:0.35rem 0.75rem;">Close</button>
                </div>

                <form id="gradeModalForm">
                    <input type="hidden" id="gradeSubmissionId" />

                    <div class="form-group">
                        <label for="gradeScore">Score</label>
                        <input type="number" id="gradeScore" min="0" required />
                    </div>

                    <div class="form-group">
                        <label for="gradeMaxScore">Max Score</label>
                        <input type="number" id="gradeMaxScore" min="1" value="100" required />
                    </div>

                    <div class="form-group">
                        <label for="gradeFeedback">Feedback (optional)</label>
                        <textarea id="gradeFeedback" rows="4" placeholder="Write feedback..."></textarea>
                    </div>

                    <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:0.5rem;">
                        <button type="button" id="gradeModalCancel" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Grade</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    $('body').append(modalHTML);

    $('#gradeModalClose, #gradeModalCancel').on('click', function () {
        closeGradeModal();
    });

    $('#gradeModal').on('click', function (e) {
        if (e.target.id === 'gradeModal') {
            closeGradeModal();
        }
    });

    $('#gradeModalForm').on('submit', function (e) {
        e.preventDefault();

        const submissionId = parseInt($('#gradeSubmissionId').val(), 10);
        const score = parseInt($('#gradeScore').val(), 10);
        const maxScore = parseInt($('#gradeMaxScore').val(), 10);
        const feedback = $('#gradeFeedback').val().trim();

        if (Number.isNaN(score) || score < 0) {
            alert('Please enter a valid non-negative score.');
            return;
        }

        if (Number.isNaN(maxScore) || maxScore <= 0) {
            alert('Please enter a valid max score greater than 0.');
            return;
        }

        if (score > maxScore) {
            alert('Score cannot be greater than max score.');
            return;
        }

        submitGrade(apiBase, submissionId, score, maxScore, feedback);
    });
}

function openGradeModal(submissionId, assignmentTitle) {
    $('#gradeSubmissionId').val(submissionId);
    $('#gradeModalTitle').text(`Grade Submission: ${assignmentTitle}`);
    $('#gradeScore').val('');
    $('#gradeMaxScore').val('100');
    $('#gradeFeedback').val('');
    $('#gradeModal').css('display', 'flex');
}

function closeGradeModal() {
    $('#gradeModal').hide();
}

function submitGrade(apiBase, submissionId, score, maxScore, feedback) {
    $.ajax({
        url: `${apiBase}/grades.php`,
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        data: {
            submissionId: submissionId,
            score: score,
            maxScore: maxScore,
            feedback: feedback
        },
        success: function (response) {
            if (response.success) {
                alert('Grade submitted successfully.');
                closeGradeModal();
                loadDashboardData(apiBase);
                loadSubmissions(apiBase);
                loadGrades(apiBase);
            } else {
                alert(response.message || 'Failed to submit grade.');
            }
        },
        error: function () {
            alert('Grade submission failed. Please try again.');
        }
    });
}

function loadGrades(apiBase) {
    const userRole = localStorage.getItem('userRole');
    const container = $('#gradesList');
    container.html('<div class="spinner"></div>');

    $.ajax({
        url: `${apiBase}/grades.php`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                response.data.forEach(function (grade) {
                    const studentInfo = (userRole === 'faculty' && grade.firstName)
                        ? `<span class="meta-item">👤 Student: <strong>${grade.firstName} ${grade.lastName}</strong></span>`
                        : '';

                    html += `
                        <div class="grade-card">
                            <h3>${grade.assignmentTitle}</h3>
                            <div class="assignment-meta">
                                <span class="meta-item">⭐ Score: <strong>${grade.score}/${grade.maxScore}</strong></span>
                                <span class="meta-item">📝 Grade: <strong>${grade.grade}</strong></span>
                                <span class="meta-item">📅 Graded: ${formatDate(grade.gradedDate)}</span>
                                ${studentInfo}
                            </div>
                            ${grade.feedback ? `<p class="mt-2"><strong>Feedback:</strong> ${grade.feedback}</p>` : ''}
                        </div>
                    `;
                });
                container.html(html);
            } else {
                container.html('<p class="text-muted text-center p-4">No grades available yet</p>');
            }
        },
        error: function () {
            container.html('<p class="text-danger text-center p-4">Failed to load grades</p>');
        }
    });
}

function setupUploadArea(apiBase) {
    const userRole = localStorage.getItem('userRole');

    if (userRole === 'faculty') {
        setupCreateAssignmentArea(apiBase);
        return;
    }

    const container = $('#uploadArea');
    const html = `
        <form id="uploadForm" class="upload-form">
            <div class="form-group">
                <label for="assignmentSelect">Select Assignment</label>
                <select id="assignmentSelect" name="assignmentId" required>
                    <option value="">-- Choose an assignment --</option>
                </select>
            </div>

            <div class="upload-dropzone" id="dropzone">
                <div class="upload-icon">📤</div>
                <h3>Click to upload or drag and drop</h3>
                <p>Supported formats: PDF, JPG, PNG, PPT, DOCX, ZIP</p>
                <input type="file" id="uploadInput" name="file" accept=".pdf,.jpg,.jpeg,.png,.ppt,.pptx,.doc,.docx,.zip" />
            </div>

            <div class="file-requirements">
                <h4>File Requirements:</h4>
                <ul>
                    <li>Maximum file size: 50MB</li>
                    <li>Supported formats: PDF, Images, PowerPoint, Word, Archives</li>
                    <li>File must be named clearly</li>
                    <li>No executable files allowed</li>
                </ul>
            </div>

            <div id="uploadProgress" class="upload-progress" style="display: none;">
                <div class="progress-bar"></div>
                <p id="progressText">0%</p>
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-3">Submit Assignment</button>
        </form>
    `;

    container.html(html);

    loadAvailableAssignments(apiBase);

    setupUploadHandlers(apiBase);
}

function setupCreateAssignmentArea(apiBase) {
    const container = $('#uploadArea');
    const html = `
        <form id="createAssignmentForm" class="upload-form">
            <div class="form-group">
                <label for="assignmentTitle">Title</label>
                <input type="text" id="assignmentTitle" name="title" placeholder="Enter assignment title" required />
            </div>

            <div class="form-group">
                <label for="assignmentSubject">Subject</label>
                <input type="text" id="assignmentSubject" name="subject" placeholder="e.g. DBMS, AI, Math" required />
            </div>

            <div class="form-group">
                <label for="assignmentDueDate">Due Date</label>
                <input type="datetime-local" id="assignmentDueDate" name="dueDate" required />
            </div>

            <div class="form-group">
                <label for="assignmentDescription">Description</label>
                <textarea id="assignmentDescription" name="description" rows="5" placeholder="Add assignment details"></textarea>
            </div>

            <div class="form-group">
                <label for="assignmentFile">Attach Document (Optional)</label>
                <input type="file" id="assignmentFile" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                <small class="text-muted">PDF, Images, or Word documents</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-3">Create Assignment</button>
        </form>
    `;

    container.html(html);

    $('#createAssignmentForm').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('title', $('#assignmentTitle').val().trim());
        formData.append('subject', $('#assignmentSubject').val().trim());
        formData.append('dueDate', $('#assignmentDueDate').val());
        formData.append('description', $('#assignmentDescription').val().trim());

        const fileInput = $('#assignmentFile')[0];
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        if (!$('#assignmentTitle').val() || !$('#assignmentSubject').val() || !$('#assignmentDueDate').val()) {
            alert('Title, subject and due date are required.');
            return;
        }

        $.ajax({
            url: `${apiBase}/assignments.php`,
            type: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    alert('Assignment created successfully.');
                    $('#createAssignmentForm')[0].reset();
                    loadDashboardData(apiBase);
                    loadAssignments(apiBase);
                } else {
                    alert(response.message || 'Failed to create assignment.');
                }
            },
            error: function () {
                alert('Create assignment failed. Please try again.');
            }
        });
    });
}

function loadAvailableAssignments(apiBase) {
    $.ajax({
        url: `${apiBase}/assignments.php`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let html = '<option value="">-- Choose an assignment --</option>';
                response.data.forEach(function (assignment) {
                    html += `<option value="${assignment.id}">${assignment.title} (Due: ${formatDate(assignment.deadline)})</option>`;
                });
                $('#assignmentSelect').html(html);
            }
        }
    });
}

function setupUploadHandlers(apiBase) {
    const dropzone = $('#dropzone');
    const uploadInput = $('#uploadInput');
    const uploadForm = $('#uploadForm');
    let selectedFile = null;

    dropzone.on('click', function () {
        uploadInput.click();
    });

    uploadInput.on('change', function () {
        handleFileSelect(this.files[0]);
    });

    dropzone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    dropzone.on('dragleave', function () {
        $(this).removeClass('drag-over');
    });

    dropzone.on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!validateFile(file)) {
            return;
        }
        selectedFile = file;
        alert(`File selected: ${file.name}`);
    }

    uploadForm.on('submit', function (e) {
        e.preventDefault();

        const assignmentId = $('#assignmentSelect').val();

        if (!assignmentId) {
            alert('Please select an assignment');
            return;
        }

        if (!selectedFile) {
            alert('Please select a file to upload');
            return;
        }

        performUpload(apiBase, assignmentId, selectedFile);
    });
}

function validateFile(file) {
    const maxSize = 50 * 1024 * 1024; 
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip'];

    if (file.size > maxSize) {
        alert('File size exceeds 50MB limit');
        return false;
    }

    if (!allowedTypes.includes(file.type)) {
        alert('File type not allowed. Please upload PDF, Image, PowerPoint, Word, or ZIP files only.');
        return false;
    }

    return true;
}

function performUpload(apiBase, assignmentId, file) {
    const formData = new FormData();
    formData.append('assignmentId', assignmentId);
    formData.append('file', file);
    formData.append('action', 'upload');

    const progressDiv = $('#uploadProgress');
    progressDiv.show();

    $.ajax({
        url: `${apiBase}/upload.php`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('token')
        },
        xhr: function () {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    $('#progressText').text(Math.round(percentComplete) + '%');
                    $('.progress-bar').css('width', percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function (response) {
            if (response.success) {
                alert('File uploaded successfully!');
                setupUploadArea(apiBase); 
            } else {
                alert('Upload failed: ' + response.message);
            }
        },
        error: function () {
            alert('Upload error. Please try again.');
        },
        complete: function () {
            progressDiv.hide();
        }
    });
}

function openPlagiarismReport(id, apiBase) {
    if (!$('#reportModal').length) {
        const modalHtml = `
            <div id="reportModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
                <div style="background:#fff; width:100%; max-width:650px; border-radius:15px; overflow:hidden; box-shadow:0 15px-50px rgba(0,0,0,0.3);">
                    <div style="background:#dc3545; color:#fff; padding:1.25rem; display:flex; justify-content:space-between; align-items:center;">
                        <h2 style="margin:0; font-size:1.25rem;">Plagiarism Investigation Report</h2>
                        <button id="closeReport" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    <div id="reportContent" style="padding:2rem;">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
        $('#closeReport').on('click', () => $('#reportModal').hide());
    }

    $('#reportModal').css('display', 'flex');
    $('#reportContent').html('<div class="spinner"></div>');

    $.ajax({
        url: `${apiBase}/plagiarism_report.php?id=${id}`,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        success: function (response) {
            if (response.success) {
                const r = response.data;
                const html = `
                <div style="text-align:center; margin-bottom:2rem;">
                    <div style="font-size:3rem; color:#dc3545; font-weight:bold;">${Number(r.plagiarismScore).toFixed(1)}%</div>
                    <div style="text-transform:uppercase; font-weight:bold; color:#666; letter-spacing:1px;">Similarity Score Detected</div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 40px 1fr; align-items:center; gap:1rem; margin-bottom:2rem;">
                    <div style="background:#fff5f5; padding:1.25rem; border-radius:10px; border:1px solid #feb2b2; text-align:center;">
                        <div style="font-size:0.8rem; color:#e53e3e; margin-bottom:0.5rem; font-weight:bold;">SUBMITTED BY (COPY)</div>
                        <div style="font-weight:bold; font-size:1.1rem;">${r.s1First} ${r.s1Last}</div>
                        <div style="font-size:0.9rem; color:#666;">${r.s1Roll}</div>
                        <div style="margin-top:0.5rem; font-size:0.8rem; color:#718096; font-style:italic;">"${r.currentFile}"</div>
                    </div>

                    <div style="text-align:center; font-size:1.5rem; color:#cbd5e0;">➔</div>

                    <div style="background:#f0fff4; padding:1.25rem; border-radius:10px; border:1px solid #9ae6b4; text-align:center;">
                        <div style="font-size:0.8rem; color:#2f855a; margin-bottom:0.5rem; font-weight:bold;">ORIGINAL SOURCE</div>
                        <div style="font-weight:bold; font-size:1.1rem;">${r.s2First || 'External Source'} ${r.s2Last || ''}</div>
                        <div style="font-size:0.9rem; color:#666;">${r.s2Roll || 'System Library'}</div>
                        <div style="margin-top:0.5rem; font-size:0.8rem; color:#718096; font-style:italic;">"${r.matchedFile || 'Database Record'}"</div>
                    </div>
                </div>

                <div style="background:#f8f9fa; padding:1rem; border-radius:8px; border-left:4px solid #cbd5e0;">
                    <strong>Investigation Summary:</strong> 
                    The system detected significant text similarity between the submissions of ${r.s1First} and ${r.s2First || 'another student'}. 
                    Evidence suggests ${r.s1First}'s work is not original for the assignment: "<em>${r.assignmentTitle}</em>".
                </div>
            `;
                $('#reportContent').html(html);
            } else {
                $('#reportContent').html('<p class="text-danger">Failed to load report.</p>');
            }
        },
        error: function () {
            $('#reportContent').html('<p class="text-danger">Error connecting to server.</p>');
        }
    });
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}
