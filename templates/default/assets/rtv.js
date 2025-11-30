jQuery.extend({

    tmpl: {
        container: '.tv-main',
        header: '.tv-header',
        footer: '.tv-footer',
        aside: '#channels',
        query: '#query',
        menu: '#mainmenu',
        toaster: '#tv-toast',
        overlay: '#tv-overlay',
        theme: '#btn-theme-switch'
    },

    rtv: {
        livesite: '',
        assets: '',
        labels: {},
        cache: {},
        xhr: null,
        logged: false,

        init: function (url, assets) {
            this.livesite = url;
            this.assets = assets;

            $.ajaxSetup({ timeout: 30000 });

            //Store labels
            if (!localStorage.getItem('rtv-labels')) {
                $.getJSON(this.livesite + '/?task=labels&format=json', (data) => {
                    localStorage.setItem('rtv-labels', JSON.stringify(data));
                    for (let key in data) {
                        if (data.hasOwnProperty(key)) {
                            $.rtv.labels[key.toLocaleLowerCase()] = data[key];
                        }
                    }
                });
            } else {
                data = JSON.parse(localStorage.getItem('rtv-labels'));
                for (let key in data) {
                    if (data.hasOwnProperty(key)) {
                        $.rtv.labels[key.toLocaleLowerCase()] = data[key];
                    }
                }
            }

            //Keep alive
            setInterval(() => {
                $.getJSON($.rtv.livesite + '/?task=keepalive&format=json', function (data) {
                    if (data.success) {
                        console.log(data.message);
                    } else {
                        top.document.location.href = $.rtv.livesite;
                    }
                });
            }, 60 * 1000);

            //Chang theme
            $($.tmpl.theme).on('click', function (e) {
                e.preventDefault();
                const mode = $(this).attr('data-mode');

                if (mode == 'dark') {
                    $(this).addClass('bi-moon-stars btn-primary').removeClass('bi-sun btn-warning');
                    $('html').attr('data-bs-theme', 'light');
                    $('.tv-bg-dark').removeClass('tv-bg-dark').addClass('tv-bg-light');
                    $(this).attr('data-mode', 'light');
                    $.getJSON($.rtv.livesite + '/?task=theme&mode=light&format=json');
                } else {
                    $(this).removeClass('bi-moon-stars btn-primary').addClass('bi-sun btn-warning');
                    $('html').attr('data-bs-theme', 'dark');
                    $('.tv-bg-light').removeClass('tv-bg-light').addClass('tv-bg-dark');
                    $(this).attr('data-mode', 'dark');
                    $.getJSON($.rtv.livesite + '/?task=theme&mode=dark&format=json');
                }

                const menu = bootstrap.Collapse.getOrCreateInstance($('#mainmenu'));
                menu.hide();
            });

            //Copy link
            $('#btn-copy').on('click', function (e) {
                e.preventDefault();
                $('#api').select();
                document.execCommand('copy');
                $.rtv.toast($.rtv.labels['copy_link'], false);
            });

            //Export link
            $('#btn-export').on('click', function (e) {
                e.preventDefault();
                let folder = $.rtv.qs('folder');
                let source = $.rtv.qs('source');
                let alias = '';
                if(source.indexOf(':') !== -1 ){
                    let parts = source.split(':',2);
                    source = parts[0];
                    alias = parts[1];
                } else {
                    alias = source;
                }                

                fetch($.rtv.livesite + '/?task=channels.export&folder=' + folder + '&source=' + source + '&format=raw')
                    .then(resp => resp.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = alias + '.m3u';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch(() => {
                        $.rtv.toast($.rtv.labels['error'], true);
                    });
            });

            //Launch lazy load
            this.lazyimage();
        },
        qs: function (key, value = null) {
            key = key.replace(/[*+?^$.\[\]{}()|\\\/]/g, "\\$&"); // escape RegEx meta chars
            var match = location.search.match(new RegExp("[?&]" + key + "=([^&]+)(&|$)"));
            if (value === null) {
                return match && decodeURIComponent(match[1].replace(/\+/g, " "));
            } else {
                let params = new URLSearchParams(location.href);
                params.set('layout', value);
                return decodeURIComponent(params.toString());
            }
        },
        lazyimage: () => {
            $('img[data-remote]:visible').each(function () {
                const img = $(this);
                const src = img.attr('src');
                const url = img.attr('data-remote');
                if (url && url != src) {
                    $.ajax({
                        url: url,
                        cache: false,
                        type: 'get',
                        dataType: 'json',
                        success: function (data, status, xhr) {
                            const timestamp = new Date().getTime();
                            img.removeClass('spinner-border');
                            img.attr('src', data.logo);
                        },
                        error: function (xhr, status, error) {
                            img.removeClass('spinner-border');
                            img.attr('src', 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=');
                        },
                        complete: function (xhr, status) {
                            img.attr('data-remote', null);
                        }
                    });
                } else {
                    img.removeClass('spinner-border');
                    img.attr('data-remote', null);
                }
            });
        },

        token: function (tokenName = null) {
            if (!tokenName) tokenName = 'token';
            $.get($.rtv.livesite + '/?task=token&name=' + tokenName).done(function (data) {
                const input = $('input#' + tokenName);
                input.attr('name', data[tokenName]);
                input.val(data.sid);
            });
        },

        login: function (selector) {
            $(selector).on('click', function (e) {
                e.preventDefault();
                const url = $('input#url').val();
                const pwd = $('input#pwd').val();
                const token = $('input#token').attr('name');
                const sid = $('input#token').val();

                data = { 'password': pwd, [token]: sid };
                const posting = $.post(url, data);
                posting.done(function (result) {
                    $.rtv.toast(result.message, result.error);
                    if (result.error) {
                        $.rtv.token();
                    } else {
                        setTimeout(1000, top.document.location.href = $.rtv.livesite);
                    }
                });
            });
        },

        toast: function (text, error = false) {
            const wrapper = $($.tmpl.toaster);
            const toast = wrapper.find('.toast:first').clone();
            toast.addClass('temp');
            toast.find('.toast-body').html(text);
            toast.addClass(error ? 'bg-danger' : 'bg-success');
            toast.appendTo('body');
            const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
            tbs.show();
            setTimeout(function () { $('.toast.temp').remove() }, 2000);
        },

        snapshot: (container) => {
            const url = container.attr('data-url');
            if (!url) return false;                  
            $.get($.rtv.livesite + '/?task=home.thumbnail&url=' + encodeURI(url), (data) => {
                container.attr('data-url', null);
                const img = container.find('img').first();
                if (img){
                    if(data.success) {
                    fetch($.rtv.livesite+'/cache/snapshots/'+data.url)
                        .then((response) => response.blob())
                        .then((blob) => {
                            const imageUrl = URL.createObjectURL(blob);
                            img.on('load', () => URL.revokeObjectURL(imageUrl));
                            img.attr('src', imageUrl);
                        });                                        
                    } else {
                        img.attr('src', 'data:'+data.mime+';'+data.url);
                    }
                }
            });

            /*
            const imgW = 432;
            const imgH = 243;
            const video = $('<video></video>');
            const source = $('<source></source>');
            video.attr({
                'type': 'video/webm',
                'preload': 'auto',
                'controls': false,
                'autoplay': false,
                'width': imgW,
                'height': imgH
            });
            source.attr({
                'src': url,
                'type': 'video/webm',
                'srcset': $.rtv.assets + '/images/video-bg.png'
            });
            source.appendTo(video);

            video.on('canplay', () => {

                //Create a canvas and paste the poster
                const canvas = $('<canvas></canvas>').get(0);
                canvas.width = imgW;
                canvas.height = imgH;
                const ctx = canvas.getContext('2d');
                ctx.fillRect(0, 0, imgW, imgH);
                ctx.drawImage(video.get(0), 0, 0, canvas.width, canvas.height);

                //Remove the video
                video.get(0).pause();
                video.remove();

                //Transform the data into a URL image/jpeg;base64,{BLOB}
                const data = canvas.toDataURL('image/jpeg');

                const img = container.find('img').first();
                if (img) img.attr('src', data);

                //Send the blob to save
                canvas.toBlob((blob) => {
                    const formdata = new FormData();
                    formdata.append('data', blob);

                    $.ajax({
                        type: 'POST',
                        url: $.rtv.livesite + '/?task=home.snapshot&url=' + encodeURI(url),
                        data: formdata,
                        contentType: false,
                        processData: false,
                        success: function () {
                            container.attr('data-url', null)
                        }
                    });
                }, 'image/jpeg');
                return;
            });
            video.on('error', () => {
                video.off('canplay');
                video.remove();
                return;
            }); 
            */           
        },
        custom: {
            bind: function () {
                $('.accordion input[type=submit]').on('click', function (e) {
                    e.preventDefault();
                    const option = $('#fld-target option[value]:selected');
                    if (option.text() == '') {
                        $.rtv.toast($.rtv.labels['import_select_target'], true);
                        return false;
                    }

                    const select = $('#fld-target');
                    const target = $('<input type="hidden" name="target">').val(select.val());
                    const token = $('input#token');
                    const form = $(this).parents('form').eq(0);
                    form.append(token);
                    form.append(target);
                    form.trigger('submit');
                    return true;
                });

                $('#btn-custom-new').on('click', function (e) {
                    e.preventDefault();
                    $('#new-modal').modal('show');
                    $(this).attr('disabled', 'disabled');
                });

                $('#new-form-close').on('click', function (e) {
                    e.preventDefault();
                    $('#new-modal').modal('hide');
                    $('#btn-custom-new').removeAttr('disabled', 'disabled');
                });

                $('#new-form-submit').on('click', function (e) {
                    e.preventDefault();
                    const listname = $('#new-listname').val();
                    if (listname.length < 2 || listname.length > 12) {
                        $.rtv.toast($.rtv.labels['new_list_invalid'], true);
                        $('#new-listname').focus();
                        return false;
                    }

                    const token = $('input#token');
                    const form = $('form#new-form');
                    form.append(token);
                    form.trigger('submit');
                    return true;
                });

                $('.btn-rem-list').on('click', function (e) {
                    e.preventDefault();

                    const card = $(this).parents('.card').eq(0);
                    const id = $(this).attr('data-id');
                    const token = $('input#token').attr('name');
                    const sid = $('input#token').val();

                    data = { 'id': id, [token]: sid };
                    const posting = $.post($.rtv.livesite + '/?task=custom.erase', data);
                    posting.done(function (result) {
                        $.rtv.token();
                        $.rtv.toast(result.message, result.error);
                        if (!result.error) {
                            const option = $('#fld-target option[value=' + id + ']');
                            card.remove();
                            option.remove();
                        }
                    });
                });
            }
        }
    }
});
