document.title = "Dashboard | Country Languages";
$(document).ready(function() {
    // Initialize variables
    let languagesData = [];
    
    $("#primary-domain-name").html(get_view_base_url());
    let primaryDomain = $("#primary-domain-name").html();
    
    // Fetch active languages from API
    function fetchActiveLanguages() {
      $.ajax({
        url: ApiForm + '/get_active_languages',
        method: 'GET',
        contentType: "application/json",
        dataType: "json",
        beforeSend: function(xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show();
        },
        success: function(response) {
          imgload.hide();
          if (response.status_code === 200 && response.data) {
            languagesData = response.data;
            renderLanguagesTable(languagesData);
          }
        },
        error: function(error) {
          imgload.hide();
          console.error('Error fetching languages:', error);
          showToast('Failed to load languages. Please try again.', 'Error', 'error');
        }
      });
    }
    
    // Render languages table with default option
    function renderLanguagesTable(languages) {
        const $tableBody = $('#languagesTableBody');
        $tableBody.empty();
        
        languages.forEach(language => {
            const isDefault = language.is_default;
            const defaultBadge = isDefault ? '<span class="default">Default</span>' : '';
            
            $tableBody.append(`
              <tr data-uuid="${language.uuid}">
                <td>${language.name} ${defaultBadge}</td>
                <td><span class="url">${isDefault ? primaryDomain + 'languages/' + language.code : primaryDomain + 'languages/' + language.code}</span></td>
                <td class="text-end" style="width: 1%;">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                      </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      ${!isDefault ? `<li><a class="dropdown-item set-default-language" href="javascript:void(0)">Set as default</a></li>` : ''}
                      <li><a class="dropdown-item delete-language" href="javascript:void(0)">Delete</a></li>
                    </ul>
                  </div>
                </td>
              </tr>
            `);
        });
        
        // Initialize DataTable if not already initialized
        if (!$.fn.DataTable.isDataTable('#languagesTable')) {
          $('#languagesTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            responsive: true
          });
        }
        
        // Attach event handlers
        $('.set-default-language').on('click', function() {
          const $row = $(this).closest('tr');
          const uuid = $row.data('uuid');
          setDefaultLanguage(uuid);
        });
        
        $('.delete-language').on('click', function() {
          const $row = $(this).closest('tr');
          const uuid = $row.data('uuid');
          deleteLanguage(uuid, $row);
        });
    }
    
    // Set default language function
    function setDefaultLanguage(uuid) {
        $.ajax({
            url: ApiForm + '/set_default_language/' + uuid,
            method: 'POST',
            contentType: "application/json",
            dataType: "json",
            beforeSend: function(xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function(response) {
                imgload.hide();
                if (response.status_code === 200) {
                    showToast(response.message || 'Default language updated successfully', 'Success', 'success');
                    fetchActiveLanguages(); // Refresh the table
                } else {
                    showToast(response.message || 'Failed to update default language', 'Error', 'error');
                }
            },
            error: function(error) {
                imgload.hide();
                console.error('Error setting default language:', error);
                let errorMessage = 'Failed to set default language. Please try again.';
                if (error.status === 404) {
                    errorMessage = 'Language not found. It may have been deleted.';
                    fetchActiveLanguages(); // Refresh the table since this language doesn't exist
                }
                showToast(errorMessage, 'Error', 'error');
            }
        });
    }
    
    // Delete language function (same as before)
    function deleteLanguage(uuid, $row) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will not be able to recover this language!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: ApiForm + '/delete_language/' + uuid,
                    method: 'DELETE',
                    contentType: "application/json",
                    dataType: "json",
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                        xhr.setRequestHeader("menu-uuid", menu_id);
                        imgload.show();
                    },
                    success: function(response) {
                        imgload.hide();
                        if (response.status_code === 200) {
                            showToast(response.message || 'Language deleted successfully', 'Success', 'success');
                            $row.fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            showToast(response.message || 'Failed to delete language', 'Error', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        imgload.hide();
                        console.error('Error deleting language:', error, xhr.responseText);
                        let errorMessage = 'Failed to delete language. Please try again.';
                        if (xhr.status === 404) {
                            errorMessage = 'Language not found.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error occurred.';
                        }
                        showToast(errorMessage, 'Error', 'error');
                    }
                });
            }
        });
    }
    

    
    // Initial fetch
    fetchActiveLanguages();
    
    // Manage domains button click handler
    $('.btn-secondary').on('click', function() {
      console.log('Manage domains clicked');
    });
});
function get_view_base_url(){
  let urlWithoutQuery = window.location.href.split('?')[0];  
  if (urlWithoutQuery.match(/\/[^\/]*\.[^\/]*$/)) {
    urlWithoutQuery = urlWithoutQuery.substring(0, urlWithoutQuery.lastIndexOf('/') + 1);
  } 
  return urlWithoutQuery;
}