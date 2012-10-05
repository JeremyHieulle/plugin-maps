<section>
    <div id="map"></div>
{if $towns|@count > 0}
    <aside id="possible_towns" title="{_T string="Choose your location"}">
        <p>{_T string="Select your town."}</p>
        <ul>
    {foreach $towns as $t}
            <li><strong>{$t.full_name_nd_ro}</strong> (<em><span class="lat">{$t.latitude}</span>/<span class="lon">{$t.longitude}</span></em>)</li>
    {/foreach}
        </ul>
    </aside>
{/if}
</section>
<script type="text/javascript">
    var _mapsBinded = function(map)
    {

        function onMapClick(e) {
            var _clat = e.latlng.lat.toString();
            var _clng = e.latlng.lng.toString();
            var _id = 'coords_' + _clat.replace('.', '_') + _clng.replace('.', '_');
            var popup = L.popup();
            popup
                .setLatLng(e.latlng)
                .setContent('<p>' + '{_T string="You clicked at %p"}'.replace('%p', '<em>' + _clat + '/' + _clng + '</em>') + '</p><p><a id="' + _id + '" href="#">{_T string="I live here!"}</a></p>')
                .openOn(map);
        }

        map.on('click', onMapClick);

        //bind "I live here" event on popupopen
        map.on('popupopen', function(e){
            var _links = $(e.popup._container).find('a');
            _a = $(_links[1]);
            _a.data('latlng', e.popup._latlng);
            _iLiveHere(_a.attr('id'));
        });


{if $town}
    {* Town is known, just display *}
        var _lat = {$town['latitude']};
        var _lon = {$town['longitude']};

        L.marker([_lat, _lon]).addTo(map)
            .bindPopup('<strong>{$member->sfullname}</strong><br/>{_T string="I live here!"}<br/><span id="removecoords">{_T string="Remove"}</span>').openPopup();
        $('#removecoords').click(function(){
            var _d = $('<div title="{_T string="Remove my coordinates"}">{_T string="Are you sure yout want to remove your coordinates from the database?"}</div>');
            _d.dialog({
                modal: true,
                width: '40%',
                buttons: {
                    '{_T string="Remove"}': function(){
                        $.ajax({
                            url: 'ajax_ilivehere.php',
                            type: 'POST',
                            data: {
                                remove: true
                            },
                            {include file="../../../../templates/default/js_loader.tpl"},
                            success: function(res){
                                if ( $.trim(res) == 'true' ) {
                                    _d.dialog('close');
                                    alert('{_T string="Your coordinates has been removed"}');
                                    //map.setView([46.830133640447386, 2.4609375], 6, true);
                                    //not very pretty... but that works for the moment :)
                                    window.location.reload();
                                } else {
                                    alert("{_T string="An error occured removing your coordinates :(" escaped="js"}")
                                }
                            },
                            error: function(){
                                alert("{_T string="An error occured removing your coordinates :(" escaped="js"}")
                            }
                        });
                    },
                    '{_T string="Cancel"}': function(){
                        $(this).dialog('close');
                    }
                }
            });
        });
{elseif $towns}
    {* Town is not known. Show possibilities *}
        var _towns = $('#possible_towns');
        _towns.dialog({
            width: '400px',
            position: ['right', 'middle']
        });

        _towns.find('li').click(function(e){
            var _elt = $(this);
            var _name = _elt.find('strong')[0].innerHTML;
            var _slat = _elt.find('.lat')[0].innerHTML;
            var _slon = _elt.find('.lon')[0].innerHTML;

            map.setView([parseFloat(_slat), parseFloat(_slon)], 13);
            var _id = 'coords_' + _slat.replace('.', '_') + _slon.replace('.', '_');
            L.marker([_slat, _slon]).addTo(map)
                .bindPopup('<p><strong>' + _name  + '</strong><br/><em>' + _slat + '/' + _slon + '</em></p><p><a id="' + _id + '" href="#">{_T string="I live here!"}</a></p>').openPopup();
        });
{/if}
    }
</script>
{include file='common_scripts.tpl'}

