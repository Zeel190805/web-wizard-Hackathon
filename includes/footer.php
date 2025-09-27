    </main>
    
    <!-- Simple Footer -->
    <footer class="text-center py-3 mt-5" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);">
        <div class="container">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> • Built with ❤️
            </small>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Global JavaScript functions and utilities
        
        // Show toast notification
        function showToast(title, text, icon = 'info') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                text: text,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
        
        // Confirm dialog
        function confirmAction(title, text, confirmText = 'Yes', cancelText = 'No') {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                reverseButtons: true
            });
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });
            
            return isValid;
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        // Update password strength meter
        function updatePasswordStrength(password, meterId) {
            const meter = document.getElementById(meterId);
            const strength = checkPasswordStrength(password);
            
            meter.className = 'password-strength';
            
            if (strength <= 1) {
                meter.classList.add('strength-weak');
            } else if (strength <= 2) {
                meter.classList.add('strength-fair');
            } else if (strength <= 3) {
                meter.classList.add('strength-good');
            } else {
                meter.classList.add('strength-strong');
            }
        }
        
        // Auto-save form data
        function autoSaveForm(formId, saveEndpoint) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(() => {
                        const formData = new FormData(form);
                        
                        fetch(saveEndpoint, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Auto-saved', 'Your changes have been saved', 'success');
                            }
                        })
                        .catch(error => console.error('Auto-save error:', error));
                    }, 2000);
                });
            });
        }
        
        // Real-time search functionality
        function setupRealTimeSearch(inputId, resultsId, searchEndpoint) {
            const searchInput = document.getElementById(inputId);
            const resultsContainer = document.getElementById(resultsId);
            
            if (!searchInput || !resultsContainer) return;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length < 2) {
                    resultsContainer.innerHTML = '';
                    return;
                }
                
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    fetch(`${searchEndpoint}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = data.html;
                        })
                        .catch(error => console.error('Search error:', error));
                }, 300);
            });
        }
        
        // File upload with progress
        function uploadFile(fileInput, progressBar, callback) {
            const file = fileInput.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressBar.setAttribute('aria-valuenow', percentComplete);
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } else {
                    showToast('Upload Failed', 'File upload failed', 'error');
                }
            });
            
            xhr.open('POST', '<?php echo BASE_URL; ?>api/upload.php');
            xhr.send(formData);
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Set theme icon on page load
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"], input[placeholder*="search" i]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    if (modal) modal.hide();
                }
            }
        });
        
        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            if (backToTop) {
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            }
        });
        
        // Analytics tracking (if needed)
        function trackEvent(category, action, label = '') {
            // Implement analytics tracking here
            console.log('Event tracked:', { category, action, label });
        }
        
        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log('Page load time:', loadTime + 'ms');
            
            // Track slow pages (over 3 seconds)
            if (loadTime > 3000) {
                trackEvent('Performance', 'Slow Page Load', window.location.pathname);
            }
        });
    </script>
    
    <!-- Back to top button -->
    <button id="back-to-top" class="btn btn-primary position-fixed" style="bottom: 20px; right: 20px; display: none; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <?php
    // Include any page-specific JavaScript
    if (isset($additional_js)) {
        echo $additional_js;
    }
    ?>
</body>
</html>
