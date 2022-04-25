$(function() {  

    var query = 
    {
        '@select': 'id, action, userBrowserName, userBrowserVersion, logTimestamp',
        'userId': 'eq(' + MapasCulturais.MapasBlame.logUserId + ')',
        '@limit': '50',
        '@page': '1',
        '@order': 'logTimestamp DESC',
    };

    function find(firstPage) {        
        if(firstPage) 
            query["@page"] = 1; 

        $.getJSON( '/api/blame/find', query, function (response, b, meta){ 
            var html = '';
            response.forEach(e => {
                var data = new Date(e.logTimestamp.date);
                    data = data.toUTCString();
                html += `
                    <tr>
                        <td> ${e.id} </td>
                        <td> ${e.action} </td>
                        <td> ${e.userBrowserName} </td>
                        <td> ${e.userBrowserVersion} </td>
                        <td> ${data} </td>
                    </tr>`;
            });

            if(firstPage)
                $('#user-log > table > tbody').html( html );        
            else
                $('#user-log > table > tbody').append( html );

            var metadata = JSON.parse( meta.getResponseHeader('API-Metadata') )
            if( metadata.page == metadata.numPages )
                $('a', '#user-log .load-more').hide();
        });
    };

    function findMore() {
        query["@page"]++;
        find();
    };
    
    function filterLogs() {
        var form = $(this).closest('#logFilter');
        var action = $('#action', form).val();
        var initDate = $('#initDate', form).val(); 
        var lastDate = $('#lastDate', form).val(); 
        
        if (initDate && lastDate) {
            query["logTimestamp"] = `BET(${initDate}, ${lastDate})`;
        } else {
            if(initDate) query["logTimestamp"] = `GTE(${initDate})`;
            if(lastDate) query["logTimestamp"] = `LTE(${lastDate})`;
        }

        if (action) query["action"] = `ILIKE(*${action}*)`;

        find(true);
    }

    find(true);

    $('button', '#logFilter').click(filterLogs);

    $('a', '#user-log .load-more').click( function() {
       findMore();
    });
    
});