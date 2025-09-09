// Main JavaScript for Vehicle Service Platform

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide flash messages
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const message = $(this).data('message') || 'Are you sure you want to delete this item?';
        
        if (confirm(message)) {
            window.location.href = url;
        }
    });

    // Dynamic form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('error', 'Please fill in all required fields.');
        }
    });

    // Real-time search
    $('#search-input').on('input', debounce(function() {
        const query = $(this).val();
        if (query.length > 2) {
            performSearch(query);
        }
    }, 300));

    // Location detection
    $('#detect-location').on('click', function() {
        if (navigator.geolocation) {
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Detecting...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    $('#latitude').val(position.coords.latitude);
                    $('#longitude').val(position.coords.longitude);
                    $('#detect-location').html('<i class="fas fa-map-marker-alt"></i> Location Detected');
                    showAlert('success', 'Location detected successfully!');
                },
                function(error) {
                    $('#detect-location').html('<i class="fas fa-map-marker-alt"></i> Detect Location');
                    showAlert('error', 'Unable to detect location. Please enter manually.');
                }
            );
        } else {
            showAlert('error', 'Geolocation is not supported by this browser.');
        }
    });

    // Auto-refresh notifications
    if ($('#notifications-count').length) {
        setInterval(function() {
            refreshNotifications();
        }, 30000); // Refresh every 30 seconds
    }

    // Invoice item management
    $('.add-invoice-item').on('click', function() {
        addInvoiceItem();
    });

    $(document).on('click', '.remove-invoice-item', function() {
        $(this).closest('.invoice-item').remove();
        calculateInvoiceTotal();
    });

    $(document).on('input', '.item-quantity, .item-price', function() {
        const row = $(this).closest('.invoice-item');
        const quantity = row.find('.item-quantity').val();
        const price = row.find('.item-price').val();
        const total = quantity * price;
        row.find('.item-total').val(total.toFixed(2));
        calculateInvoiceTotal();
    });
});

// Utility Functions
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

function showAlert(type, message) {
    const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.flash-messages').html(alertHtml);
    setTimeout(() => $('.alert').fadeOut(), 5000);
}

function performSearch(query) {
    // Implement search functionality
    $.ajax({
        url: '/api/search.php',
        method: 'GET',
        data: { q: query },
        success: function(data) {
            displaySearchResults(data);
        },
        error: function() {
            showAlert('error', 'Search failed. Please try again.');
        }
    });
}

function displaySearchResults(results) {
    const resultsContainer = $('#search-results');
    if (results.length === 0) {
        resultsContainer.html('<div class="alert alert-info">No results found.</div>');
        return;
    }
    
    let html = '';
    results.forEach(function(result) {
        html += `
            <div class="service-card">
                <h5>${result.name}</h5>
                <p>${result.description}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary">${result.category}</span>
                    <span class="fw-bold">${result.price}</span>
                </div>
            </div>
        `;
    });
    
    resultsContainer.html(html);
}

function refreshNotifications() {
    $.ajax({
        url: '/api/notifications.php',
        method: 'GET',
        success: function(data) {
            $('#notifications-count').text(data.unread_count);
            if (data.unread_count > 0) {
                $('#notifications-count').removeClass('d-none');
            } else {
                $('#notifications-count').addClass('d-none');
            }
        }
    });
}

function addInvoiceItem() {
    const itemHtml = `
        <div class="row invoice-item mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="item_description[]" placeholder="Description" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control item-quantity" name="item_quantity[]" placeholder="Qty" value="1" min="1" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control item-price" name="item_price[]" placeholder="Price" step="0.01" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control item-total" name="item_total[]" placeholder="Total" readonly>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-invoice-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    $('#invoice-items').append(itemHtml);
}

function calculateInvoiceTotal() {
    let subtotal = 0;
    $('.item-total').each(function() {
        const value = parseFloat($(this).val()) || 0;
        subtotal += value;
    });
    
    const taxRate = parseFloat($('#tax-rate').val()) || 0;
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;
    
    $('#subtotal').val(subtotal.toFixed(2));
    $('#tax-amount').val(taxAmount.toFixed(2));
    $('#total-amount').val(total.toFixed(2));
}

// Service request status updates
function updateServiceStatus(requestId, status) {
    $.ajax({
        url: '/api/update-service-status.php',
        method: 'POST',
        data: {
            request_id: requestId,
            status: status
        },
        success: function(data) {
            if (data.success) {
                location.reload();
            } else {
                showAlert('error', data.message || 'Failed to update status.');
            }
        },
        error: function() {
            showAlert('error', 'Failed to update status. Please try again.');
        }
    });
}

// Rating system
function setRating(rating) {
    $('#rating-input').val(rating);
    updateStarDisplay(rating);
}

function updateStarDisplay(rating) {
    $('.rating-star').each(function(index) {
        if (index < rating) {
            $(this).removeClass('far').addClass('fas');
        } else {
            $(this).removeClass('fas').addClass('far');
        }
    });
}

// Initialize rating stars
$(document).on('click', '.rating-star', function() {
    const rating = $(this).data('rating');
    setRating(rating);
});