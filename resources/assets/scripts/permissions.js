/// <reference path="../../../typings/tsd.d.ts" />

function refreshPermissions() {
    return $.ajax({
        url: '/v1/permissions',
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", $("#auth_token").val());
        }
    });
}

function refreshRoles() {
    var authenticationToken = $("#auth_token").val();

    if( !authenticationToken || $.trim(authenticationToken) == '' ) {
        alert('Enter valid authentication token to view/update roles and permissions.');
        return;
    }

    $(':checkbox').prop('checked', false);
    $(':checkbox').prop('disabled', false);

    $.blockUI({ message: '<h1>Loading...</h1>' });

    $.ajax({
        url: '/v1/roles',
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", $("#auth_token").val());
        },
        success: function(roles) {
            var headerRowHtml = "<tr><th></th>";
            $(roles).each(function(index, role){
                headerRowHtml += "<th><div>" + role.label + "</div>" + 
                                    "<div><input type='checkbox' value='" + role.id + "' "+ 
                                    "onclick=\"$(':checkbox[name^=rp_][data-role=" + role.id + "]').prop('checked', $(this).is(':checked'));\" /></div></th>"
            });
            headerRowHtml += "</tr>";

            $("#role-permissions-table > thead").html(headerRowHtml);
            $("#role-permissions-table > tbody").html("");

            refreshPermissions().done(function(permissions){
                $(permissions).each(function(index, permission){
                    var permissionRowHtml = "<tr>";
                    permissionRowHtml += "<td>" + permission.label + "</td>";
                    $(roles).each(function(index, role){
                        permissionRowHtml += "<td><input type='checkbox' name='rp_" + role.id + "_" + permission.id + "' " + 
                                "data-role='" + role.id + "' data-permission='" + permission.id + "'/></td>";
                    });
                    permissionRowHtml += "</tr>";

                    $("#role-permissions-table > tbody").append(permissionRowHtml);
                });

                $(roles).each(function(index, role){
                    if( $.isArray(role.permissions) && role.permissions.length > 0 ) {
                        $(role.permissions).each(function(index, permission){
                            $('input[data-role=' + role.id + '][data-permission=' + permission.id + ']').prop('checked', true);
                        });
                    }
                });
            }).fail(function(){
                $.unblockUI();
                alert('Error occurred while fetching role permission details. Please try again.');
            }).always(function(){
                $.unblockUI();
            });
        },
        error: function() {
            $(':checkbox').prop('disabled', true);
            $.unblockUI();
            alert('Error occurred while fetching role permission details. Please try again.');
        }
    });
}

function updateRolePermissions() {
    var authenticationToken = $("#auth_token").val();

    if( !authenticationToken || $.trim(authenticationToken) == '' ) {
        alert('Enter valid authentication token to view/update roles and permissions.');
        return;
    }

    $.blockUI({ message: '<h1>Please wait...</h1>' });

    var data = {};

    $(":checkbox[name^=rp_]:checked").each(function () {
        var checkbox = $(this);
        var roleId = checkbox.attr('data-role');
        var permissionId = checkbox.attr('data-permission');

        if (!data[checkbox.attr('data-role')]) {
            data[roleId] = [];
        }

        data[roleId].push(permissionId);
    });

    $.ajax({
        url: '/v1/roles',
        method: 'PUT',
        data: data,
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", $("#auth_token").val());
        },
        success: function(response) {

        },
        error: function() {
            $.unblockUI();
            alert('Error occurred while saving role permission details. Please try again.');
        },
        complete: function() {
            $.unblockUI();
        }
    });
}
