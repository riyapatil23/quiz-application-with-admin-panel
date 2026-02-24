// SmartQuiz JavaScript Functions

class QuizTimer {
    constructor(duration, onTick, onComplete) {
        this.duration = duration; // in seconds
        this.remaining = duration;
        this.onTick = onTick;
        this.onComplete = onComplete;
        this.interval = null;
        this.isRunning = false;
    }

    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.interval = setInterval(() => {
            this.remaining--;
            this.onTick(this.remaining);
            
            if (this.remaining <= 0) {
                this.stop();
                this.onComplete();
            }
        }, 1000);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
            this.isRunning = false;
        }
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// Quiz Management Class
class QuizManager {
    constructor() {
        this.currentQuestion = 0;
        this.questions = [];
        this.answers = {};
        this.timer = null;
        this.startTime = null;
    }

    init(questions, timeLimit) {
        this.questions = questions;
        this.startTime = new Date();
        
        // Initialize timer
        if (timeLimit > 0) {
            this.timer = new QuizTimer(
                timeLimit * 60, // convert minutes to seconds
                (remaining) => this.updateTimer(remaining),
                () => this.autoSubmit()
            );
            this.timer.start();
        }

        this.showQuestion(0);
        this.updateProgress();
    }

    showQuestion(index) {
        if (index < 0 || index >= this.questions.length) return;
        
        this.currentQuestion = index;
        const question = this.questions[index];
        
        // Update question display
        document.getElementById('question-number').textContent = index + 1;
        document.getElementById('question-text').textContent = question.question_text;
        document.getElementById('option-a').textContent = question.option_a;
        document.getElementById('option-b').textContent = question.option_b;
        document.getElementById('option-c').textContent = question.option_c;
        document.getElementById('option-d').textContent = question.option_d;
        
        // Clear previous selections
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        // Show previous answer if exists
        if (this.answers[question.question_id]) {
            const selectedBtn = document.querySelector(`[data-option="${this.answers[question.question_id]}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('selected');
            }
        }
        
        // Update navigation buttons
        this.updateNavigation();
        this.updateProgress();
    }

    selectOption(questionId, option) {
        this.answers[questionId] = option;
        
        // Update UI
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        const selectedBtn = document.querySelector(`[data-option="${option}"]`);
        if (selectedBtn) {
            selectedBtn.classList.add('selected');
        }
    }

    nextQuestion() {
        if (this.currentQuestion < this.questions.length - 1) {
            this.showQuestion(this.currentQuestion + 1);
        }
    }

    previousQuestion() {
        if (this.currentQuestion > 0) {
            this.showQuestion(this.currentQuestion - 1);
        }
    }

    updateNavigation() {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentQuestion === 0;
        }
        
        if (nextBtn) {
            nextBtn.style.display = this.currentQuestion === this.questions.length - 1 ? 'none' : 'inline-block';
        }
        
        if (submitBtn) {
            submitBtn.style.display = this.currentQuestion === this.questions.length - 1 ? 'inline-block' : 'none';
        }
    }

    updateProgress() {
        const progress = ((this.currentQuestion + 1) / this.questions.length) * 100;
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.textContent = `${this.currentQuestion + 1} / ${this.questions.length}`;
        }
    }

    updateTimer(remaining) {
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            timerElement.textContent = this.timer.formatTime(remaining);
            
            // Add warning class when less than 5 minutes remaining
            if (remaining <= 300) {
                timerElement.classList.add('warning');
            }
        }
    }

    autoSubmit() {
        alert('Time is up! Quiz will be submitted automatically.');
        this.submitQuiz();
    }

    submitQuiz() {
        if (this.timer) {
            this.timer.stop();
        }

        // Calculate time taken
        const endTime = new Date();
        const timeTaken = Math.floor((endTime - this.startTime) / 1000); // in seconds

        // Create form data
        const formData = new FormData();
        formData.append('quiz_id', document.getElementById('quiz-id').value);
        formData.append('time_taken', timeTaken);
        
        // Add answers
        for (const [questionId, answer] of Object.entries(this.answers)) {
            formData.append(`answers[${questionId}]`, answer);
        }

        // Submit via AJAX
        fetch('submit_quiz.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `result.php?result_id=${data.result_id}`;
            } else {
                alert('Error submitting quiz: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the quiz.');
        });
    }

    getAnsweredCount() {
        return Object.keys(this.answers).length;
    }

    getUnansweredQuestions() {
        const unanswered = [];
        this.questions.forEach((question, index) => {
            if (!this.answers[question.question_id]) {
                unanswered.push(index + 1);
            }
        });
        return unanswered;
    }
}

// Form validation functions
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });

    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        numbers: /\d/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    for (const check in checks) {
        if (checks[check]) strength++;
    }

    return {
        score: strength,
        checks: checks,
        level: strength < 2 ? 'weak' : strength < 4 ? 'medium' : 'strong'
    };
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
}

// Initialize quiz when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and popovers if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Global quiz manager instance
let quizManager = null;
