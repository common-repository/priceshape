jQuery( document ).ready( function( $ ) {
    $( "#login-form" ).submit( function ( event ) {
        event.preventDefault();
        let data = 'action=prsLoginFormAjax&' + $( this ).serialize();
        $.post( ajaxObject['ajaxUrl'], data, function( response ) {
            if ( 0 == response.length ) {
                location.reload( true );
            }
            if ( 0 < response.length ) {
                let json = JSON.parse( response );
                let warningBox = $( "#prs-login-warning" );
                warningBox.html('');
                for ( let key in json ) {
                    let messages = json[ key ];
                    for ( let i = 0; i < messages.length; i++ ) {
                        warningBox.append( "<p>" + messages[i] + "</p>" )
                    }
                }
            }
        });
    });

    $( ".priceshape-turn" ).on( 'click', function( event ) {
        event.preventDefault();
        let elem = $( this );
        let data = 'action=prsItemTurnAjax&prs-turn=' + elem.data( 'turn' ) + '&prod=' + elem.data( 'prod' );
        $.post( ajaxObject['ajaxUrl'], data, function( response ) {
            let itemColumnText = elem.closest( '.column-prs' ).children( 'strong' );
            if ( "prs-on" == response ) {
                itemColumnText.text( 'ON' );
                elem.text( 'Remove from PriceShape' );
                elem.attr( 'data-turn', 'prs-off' );
                elem.data( 'turn', 'prs-off' );
            }
            if ( "prs-off" == response ) {
                itemColumnText.text( 'OFF' );
                elem.text( 'Add to PriceShape' );
                elem.attr( 'data-turn', 'prs-on' );
                elem.data( 'turn', 'prs-on' );
            }
        });
    });

    $( ".price-operation" ).on( 'click', function( event ) {
        event.preventDefault();
        let elem = $( this );
        let data = 'action=prsItemApproveAjax';

        let dataObj = {
            priceHandler: 'price-handler',
            field_id    : 'field_id',
        };

        for ( let key of Object.keys( dataObj ) ) {
            let value = elem.data( dataObj[key] );
            data += `&${key}=${value}`;
        }

        $.post( ajaxurl, data, function( response ) {
            let itemColumnText = elem.closest( '.column-new_value' );
            let content = response;
            itemColumnText.html( '<strong>' + content + '</strong>' );
        });
    });

    $( "#prs-try-again" ).on( 'click', function( event ) {
        event.preventDefault();
        let data = 'action=prsTryAgainAjax';

        $.post( ajaxurl, data, function( response ) {
            if ( false !== response) {
                location.reload( true );
            }
        });
    });

    $( "#prs-support-btn" ).on( 'click', function( event ) {
        event.preventDefault();
        let data = 'action=prsSupportBtn';
        let elem = $( this );

        $.post( ajaxurl, data, function( response ) {
            let itemColumnText = elem.closest( '.wrapper-support' );
            if ( false == response ) {
                let content = 'During Sending Email Error Occurred. Contact Support by mail <a href="mailto:priceshape@gmail.com">priceshape@gmail.com</a>';
                itemColumnText.html( '<strong>' + content + '</strong>' );
            }
            if ( true == response ) {
                let content = 'Error Report Has Been Successfully Sent';
                itemColumnText.html( '<strong>' + content + '</strong>' );
            }
        });
    });

    $( "#hide-old-price" ).on( 'click', function( event ) {
        event.preventDefault();
        let elem = $( this );
        let hide = elem.attr( 'data-hide' );
        let data = 'action=prsHideOldPrice&hide=' + hide;

        $.post( ajaxurl, data, function( response ) {
            console.log( response );
            location.reload( true );
        });
    });

    $( "#auto-update-price" ).on( 'click', function( event ) {
        event.preventDefault();
        let elem = $( this );
        let autoUpdate = elem.attr( 'data-auto-update' );
        let data = 'action=prsAutoUpdatePrice&prsAutoUpdate=' + autoUpdate;

        $.post( ajaxurl, data, function( response ) {
            console.log( response );
            location.reload(true);
        });
    });

    $( "#update-only-sale-price" ).on( 'click', function( event ) {
        event.preventDefault();
        let elem = $( this );
        let updateOnlySalePrice = elem.attr( 'data-update-only-sale-price' );
        let data = 'action=prsUpdateOnlySalePrice&prsUpdateOnlySalePrice=' + updateOnlySalePrice;

        $.post( ajaxurl, data, function( response ) {
            console.log( response );
            location.reload(true );
        });
    });

});
