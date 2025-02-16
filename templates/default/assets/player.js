(function ($) {
    $.extend({
        uriGet: function () {
            var url_string = location.href;
            var url = new URL(url_string);
            var val = url.searchParams.get(arguments[0]);
            return val;
        }
    });
})(jQuery);

jQuery.extend({
    player: {
        loaded: false,      /* Toogle if the player is already loaded */
        url: null,          /* URL that provides the sources */
        json: null,         /* Response from URL */
        sequence: 0,        /* Sequence of the buffered audio */
        buffered: false,    /* Whethr the audio is buffered */
        context: null,      /* Audio Context */
        list: null,         /* List of URL */
        audio: null,        /* HTML audio object */
        muted: false,       /* Audio muted */
        isplaying: false,   /* Toogle play/pause */
        title: '',          /* Audio Title */
        logo: '',           /* Logo station */
        ping: 0,            /* Request timestamp */
        pong: 0,            /* Response timestamp */
        info: null,         /* Information about the buffer */
        icy: null,          /* Informations IceCast */
        iterate: 0,         /* Iterated requests */
        lapse: 0,           /* Time diff between the ping and the pong */
        record: false,      /* Whether the recording is enabled */
        recordtime: 0,      /* Recording time */
        timer: null,        /* Timer */
        enabled: true,      /* Player enabled */
        debug: true,       /* Debug */

        equalizer: {
            active: false,  /* Toggle if the visualization is active */
            canvas: null,   /* Canvas for the equializer */
            analyser: null, /* Frequencies analyzer for the visualisation */
            blength: 0,     /* Analyser Buffer length */
            data: null,     /* Data */
            ctx: null,      /* Graphical context for the canvas */
            height: 0,
            width: 0
        },

        //Load the sequence 
        init: function (url) {

            if (!this.loaded) {
                this.url = url;
                this.isplaying = false;
                this.record = false;
                this.loaded = true;
                this.buttons.disable();
                this.wait(true);
            }
            this.ping = Date.now();
            $.ajax({
                url: url,
                type: 'GET',
                data: {
                    'ping': this.ping,
                    'record': this.record
                },
                dataType: 'json'
            }).then(function (data) {
                $.player.json = data;
                if (data.success) {
                    $.player.name = data.name;
                    $.player.title = data.title;
                    $.player.logo = data.logo;
                    $.player.pong = parseInt(data.pong);
                    $.player.lapse = parseFloat(data.lapse);
                    $.player.sequence = parseInt(data.sequence);
                    $.player.buffered = data.buffered;
                    $.player.info = data.info;
                    $.player.icy = data.icy;
                    $.player.wait(false);
                    $.player.about();
                    //For buffered audio
                    if (data.buffered) {
                        $.player.list = [];

                        // Re-try
                        if (data.sources === null) {
                            $.player.iterate++;
                            if ($.player.iterate < 3) {
                                $.rtv.toast($.rtv.labels['radio_retry'] + url);
                                $.player.wait(true);
                                $.player.init(url);
                            } else {
                                $.player.buttons.disable();
                                $.player.wait(false);
                                $.rtv.toast($.rtv.labels['radio_error_loading'], true);
                            }
                        }
                        $.player.iterate = 0;

                        $.each(data.sources, function (i, link) {
                            if ($.player.list.indexOf(link) === -1) {
                                $.player.list.push(link);
                                $.player.buffer.load(link);
                            }
                        });
                        if ($.player.debug) {
                            console.log('Loaded list: ' + $.player.list.length + ' tracks');
                            console.log($.player.list);
                        }

                        //For continuous streaming
                    } else {
                        $('#record').remove();
                        $.player.list = data.sources;
                        $.player.buttons.enable();
                    }
                } else {
                    $.player.buttons.disable();
                    $.player.wait(false);
                    $.rtv.toast($.rtv.labels['radio_error_loading'], true);
                }
            }).fail(function () {
                if ($.player.debug) console.log('Error loading the source. Please wait or refresh.', true);
            });
        },
        play: function () {
            if (this.json.buffered) {
                this.buffer.flush();
            } else {
                this.stream.flush();
            }            

            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: this.name,
                    artist: this.title,
                    artwork: [{ src: this.logo, sizes: '150x150', type: 'image/jpg' }]
                });

                navigator.mediaSession.setActionHandler('play', function () {
                    $.player.play();
                });

                navigator.mediaSession.setActionHandler('pause', function () {
                    $.player.pause();
                });
            }
        },
        pause: function () {
            if (this.json.buffered) {
                this.buffer.stop();
            } else {
                this.stream.stop();
            }
        },
        volume: function (value) {
            if (value < 5) {
                $('#volume').trigger('click');
                return;
            }
            let v = (value / 100).toFixed(2);
            if (this.json.buffered) {
                if (this.buffer.volume) this.buffer.volume.gain.value = (2 * v - 1);
            } else {
                if (this.audio) this.audio.volume = v;
            }
            this.muted = false;
            const icon = $('#volume').find('i').first();
            icon.removeClass('bi-volume-mute');
            icon.addClass('bi-volume-up');
        },
        mute: function () {
            if (this.json.buffered) {
                if (this.buffer.volume) this.buffer.volume.gain.value = -1;
            } else {
                if (this.audio) this.audio.volume = 0;
            }
            this.muted = true;
        },
        about: function () {

            $('#record').removeClass('d-none');
            $('#player-title').text($.player.title);
            $('#player-latency').text((this.pong - this.ping) / 1000 + 's');
            $('#player-buffer-length').text(this.lapse + 's');
            if ($('#player-info').find('li').length > 2) return;

            if (this.buffered && !this.info) return;
            if (!this.buffered && this.icy) {
                this.info = $.extend(this.info, this.icy);
            }

            $.each(this.info, function (key, value) {
                switch (key) {
                    case 'BANDWIDTH':
                        value = Math.ceil(parseInt(value) / 1000) + ' kHz';
                        break;
                    case 'SAMPLERATE':
                        value = Math.ceil(parseInt(value) / 1000) + ' kbps';
                        break;
                    case 'BITRATE':
                        value = Math.ceil(parseInt(value) / 1000) + ' kHz';
                        break;
                    case 'NAME':
                    case 'PROGRAM-ID':
                    case 'METAINT':
                    case 'URL':
                        key = null;
                        break;
                }

                if (key) {
                    let str = key.toLocaleLowerCase();
                    if (('player_' + str) in $.rtv.labels) {
                        str = $.rtv.labels['player_' + str];
                    } else {
                        str = str.charAt(0).toUpperCase() + str.slice(1);
                    }
                    const li = $('<li></li>').addClass('list-group-item text-muted').html(str + ': ' + value);
                    li.prependTo($('#player-info'));
                }
            });
        },
        wait: function (state) {
            if (state) {
                $('#loading-buffer').removeClass('d-none');
            } else {
                $('#loading-buffer').addClass('d-none');
            }
        },
        uri: function () {
            const url = new URL(top.location.href);
            return url.searchParams.get(arguments[0]);
        },
        buttons: {
            enable: function () {
                $.player.enabled = true;
                $('#play').removeClass('text-muted');
                $('#volume').removeClass('text-muted');
                $('#record').removeClass('text-muted');
            },
            disable: function () {
                $.player.enabled = false;
                $('#play').addClass('text-muted');
                $('#volume').addClass('text-muted');
                $('#record').addClass('text-muted');
            },
            toggle: function () {
                if ($.player.isplaying) {
                    $('#play i').removeClass('bi-play');
                    $('#play i').addClass('bi-pause');
                    $('#record i').removeClass('bi-pause-fill');
                    $('#record i').addClass('bi-record-fill');
                } else {
                    $('#play i').addClass('bi-play');
                    $('#play i').removeClass('bi-pause');
                    $('#record i').addClass('bi-pause-fill');
                    $('#record i').removeClass('bi-record-fill');
                }
            }
        },
        buffer: {
            items: [],
            volume: null,
            source: null,
            startedAt: null,
            pausedAt: null,

            //Load the buffer for each track
            load: function (url) {

                //Create Audio context
                if (!$.player.context) {
                    $.player.context = new (window.AudioContext || window.webkitAudioContext)();
                    this.volume = $.player.context.createGain();
                    this.volume.gain.value = 1;
                    this.volume.connect($.player.context.destination);
                }

                //Load array buffer for the given url
                $.ajax({
                    url: url,
                    type: 'GET',
                    xhrFields: { responseType: 'arraybuffer' }
                }).then(function (response) {

                    if (!response || !(response instanceof ArrayBuffer)) {
                        $.player.wait(false);
                        $.rtv.toast($.rtv.labels['radio_invalid'], true);
                        return;
                    };

                    $.player.context.decodeAudioData(response, function (buffer) {
                        $.player.loaded = true;
                        $.player.buffer.items.push(buffer);
                        $.player.buttons.enable();
                    });
                }).fail(function () {
                    $.player.wait(false);
                    $.rtv.toast($.rtv.labels['radio_notfound'], true);
                    if ($.player.debug) console.log('Error while buffering ' + url);
                });
            },
            flush: function () {

                //Create the source node, stereo buffer, 2 seconds long, 44.1 kHz sample rate
                this.source = $.player.context.createBufferSource(2, 22050 * 2, 44100);
                this.source.loop = false;
                this.source.buffer = this.items.shift();
                this.source.connect(this.volume);
                this.source.onended = () => {
                    if (this.paused) return;
                    if ($.player.debug) console.log('Buffer ended');

                    $.player.buffer.startedAt = 0;
                    $.player.buffer.pausedAt = 0;

                    //Any other source to play?
                    if (this.items.length) this.flush();

                    //Load next batch
                    $.player.init($.player.url);
                };

                this.paused = false;
                if (this.pausedAt) {
                    this.startedAt = Date.now() - this.pausedAt;
                    this.source.start(0, this.pausedAt / 1000);
                }
                else {
                    this.startedAt = Date.now();
                    this.source.start(0);
                }
                $.player.equalizer.active = false;
                $.player.visualization();
            },
            stop: function () {
                if (this.source) {
                    this.source.stop();
                    this.pausedAt = Date.now() - this.startedAt;
                    this.paused = true;
                }
            }
        },
        stream: {
            data: [],
            load: function (url) {
                if (!$.player.context) {
                    $.player.context = new (window.AudioContext || window.webkitAudioContext)();
                }

                $.player.audio = new Audio(url);                
                $.player.audio.crossOrigin = 'anonymous';

                //For Icecast, we refresh the title every 15 secs
                if ($.player.icy) {
                    setInterval(() => {
                        $.get($.rtv.livesite + '/?task=listen.refresh' +
                            '&folder=' + $.player.uri('folder') +
                            '&source=' + $.player.uri('source') +
                            '&id=' + $.player.uri('id'), function (response) {
                                if ($.player.isplaying && response.success) {
                                    $.player.title = response.title;
                                    $.player.about();
                                }
                            });
                    }, 15 * 1000);
                }
            },
            flush: function () {
                if (!$.player.audio) this.load($.player.list);
                let promise = $.player.audio.play();
                if (promise !== undefined) {
                    promise.then(_ => {
                        $.player.visualization();
                        $.player.loaded = true;                                               
                    }).catch(error => {
                        $.player.isplaying = false;
                        $('#play i').addClass('bi-play');
                        $('#volume i').removeClass('bi-pause');
                        $.rtv.toast($.rtv.labels['radio_error_loading'], true);
                    });
                }
                
            },
            stop: function () {
                if ($.player.audio) $.player.audio.pause();
            }
        },
        //Creates a visualisation from the current track in the player
        visualization: function () {

            if (!this.equalizer.active) {
                this.equalizer.analyser = $.player.context.createAnalyser();
                this.equalizer.active = true;
                if (this.buffered) {
                    $.player.buffer.source.connect(this.equalizer.analyser);
                } else {
                    try {
                        const source = this.context.createMediaElementSource($.player.audio);
                        source.connect(this.equalizer.analyser);                                              
                    } catch (e) {
                        if ($.player.debug) console.error('Error rendering the visualization.');
                        return;
                    }
                }
            }

            let ratio = $(window).width()/$(window).height();
            if(ratio > 1) ratio = 1/ratio;            
            const canvas = $('#equalizer');
            canvas.removeClass('d-none');            
            this.equalizer.canvas = canvas.get(0);
            this.equalizer.canvas.width = 0.85 * canvas.parent().width();
            this.equalizer.canvas.height = ratio * $('#player-icon').height();

            this.equalizer.analyser.connect($.player.context.destination);
            this.equalizer.analyser.fftSize = 256;
            this.equalizer.blength = this.equalizer.analyser.frequencyBinCount;
            this.equalizer.data = new Uint8Array(this.equalizer.blength);
            
            this.equalizer.ctx = this.equalizer.canvas.getContext('2d');
            this.equalizer.width = this.equalizer.canvas.width;
            this.equalizer.height = this.equalizer.canvas.height;

            this.renderFrame();

        },
        renderFrame: function () {
            requestAnimationFrame($.player.renderFrame);

            $.player.equalizer.analyser.getByteFrequencyData($.player.equalizer.data);

            $.player.equalizer.ctx.fillStyle = '#b1b8be';
            $.player.equalizer.ctx.globalAlpha = 1;
            $.player.equalizer.ctx.fillRect(0, 0, $.player.equalizer.width, $.player.equalizer.height);

            for (var i = 0, x = 0, ratio=0, barWidth = 0, barHeight = 0; i < $.player.equalizer.blength; i++) {
                ratio = $(window).width()/$(window).height();                
                barWidth = ($.player.equalizer.width / $.player.equalizer.blength) * 3;
                if(ratio > 1) {
                    barHeight = $.player.equalizer.data[i] / (ratio / 0.5);
                } else {
                    barHeight = $.player.equalizer.data[i] * (ratio * 0.25);
                }

                var r = 128 - 6 * barHeight * (i / $.player.equalizer.blength);
                var g = 128 + 6 * barHeight * (i / $.player.equalizer.blength);
                var b = 128 + 12 * barHeight * (i / $.player.equalizer.blength);

                $.player.equalizer.ctx.fillStyle = 'rgb(' + r + ',' + g + ',' + b + ')';
                $.player.equalizer.ctx.fillRect(x, $.player.equalizer.height - barHeight, barWidth, barHeight);
                x += barWidth + 1;
            }
        },
    }
});

jQuery(document).ready(function ($) {

    $('#volume-slider').css('width', $('#volume-slider').parent().width() + 'px');
    $('#equalizer').attr({
        'width': $('#equalizer').parent().width(),
        'height': 0
    });

    // After orientationchange, add a one-time resize event
    $(window).on('orientationchange', function () {
        $(window).on('resize', function () {
            let ratio = $(window).width()/$(window).height();
            if(ratio > 1) ratio = 1/ratio;
            $('#volume-slider').css('width', $('#volume-slider').parent().width() + 'px');
            $('#equalizer').attr({
                'width': 0.85 * $('#equalizer').parent().width(),
                'height': ratio * $('#player-icon').height(),
            });
            $.player.visualization();            
        });
    });

    $('#play').on('click', function (event) {
        event.preventDefault();
        if (!$.player.enabled) return;

        if (!$.player.isplaying) {
            $.player.play();
        } else {
            $.player.pause();
        }
        $.player.isplaying = !$.player.isplaying;
        $.player.buttons.toggle();
    });

    $('#volume').on('click', function (event) {
        event.preventDefault();
        if (!$.player.enabled) return;

        const icon = $(this).find('i').first();
        if (!$.player.muted) {
            icon.removeClass('bi-volume-up');
            icon.addClass('bi-volume-mute');
            $('#volume-value').css('width', '0');
            $('#volume-handler').css('left', '0');
            $.player.mute();
        } else {
            icon.removeClass('bi-volume-mute');
            icon.addClass('bi-volume-up');
            $('#volume-value').css('width', '100%');
            $('#volume-handler').css('left', 'calc(100% - 14px)');
            $.player.volume(100);
        }
    });

    $('#record').on('click tap', function (event) {
        event.preventDefault();
        if (!$.player.enabled) return;
        if (!$.player.buffered) return;

        const icon = $(this).find('i').first();
        if (!$.player.timer) {
            $.get($.rtv.livesite + '/?task=listen.record&do=start', () => {
                icon.addClass('text-danger');
                $('#record-timer').removeClass('d-none');
                $('#record-download').addClass('d-none');
                $('#record-timer').text(' --:--:--');
                $.player.record = true;
                $.player.timer = setInterval(() => {
                    if ($.player.isplaying) {
                        $.player.recordtime++;
                        // Limit the record to 1 hour
                        if ($.player.recordtime > 3600) {
                            $('#record').trigger('click');
                            $.rtv.toast($.rtv.labels['radio_recording_limit']);
                            return;
                        }
                        $('#record-timer').text(' ' + secondsToHHMMSS($.player.recordtime));
                    }
                }, 1000);
            });
        } else {
            icon.removeClass('text-danger');
            $('#record-timer').addClass('d-none');
            $('#record-download').removeClass('d-none');
            $('#record-timer').text(' --:--:--');
            if ($.player.timer) clearInterval($.player.timer);
            $.player.timer = null;
            $.player.recordtime = 0;
            $.player.record = false;
        }
    });

    $('#record-download').on('click', function (event) {
        event.preventDefault();
        $.ajax({
            url: $.rtv.livesite + '/?task=listen.record&do=download',
            method: 'GET',
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data) {
                const a = document.createElement('a');
                const url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = (new Date().toISOString().replace(/\D/g, "")) + '.mp3';
                document.body.append(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                $('#record-download').addClass('d-none');
            }
        });
    });


    //Slider for Mobile
    $('#volume-slider').on('touchstart', function (e) {
        $('#volume-slider').on('touchmove', function handler(ev) {
            e.preventDefault();
            var touch = ev.originalEvent.touches[0] || ev.originalEvent.changedTouches[0];
            var elm = $(this).offset();
            var pos = touch.pageX - elm.left;
            var max = $('#volume-slider').width();
            if (pos > 0 && pos < max - 7) {
                const percent = parseInt(pos) / parseInt(max) * 100;
                $('#volume-value').css('width', percent + '%').attr('data-value', percent);
                $('#volume-handler').css('left', percent + '%');
                $.player.volume(parseInt($('#volume-value').attr('data-value')));
            }
        });
    });
    $('#volume-slider').on('touchend', function () {
        $('#volume-slider').off('touchmove');
    });
    $('#volume-handler').on('touchstart', function () {
    });
    $('#volume-handler').on('touchend', function () {
        $('#volume-slider').off('touchmove');
    });

    //Slider for PC
    $('#volume-slider').on('mousedown', function (e) {
        $('#volume-slider').on('mousemove', function handler(ev) {
            if (ev.offsetY > 8 || ev.offsetY < 1) return;
            const width = $('#volume-slider').width();
            const percent = parseInt(ev.offsetX) / parseInt(width) * 100;
            $('#volume-value').css('width', percent + '%').attr('data-value', percent);;
            $('#volume-handler').css('left', percent + '%');
            $.player.volume(parseInt($('#volume-value').attr('data-value')));
        });
    });
    $('#volume-slider').on('mouseup', function () {
        $('#volume-slider').off('mousemove');
    });
    $('#volume-handler').on('mousedown', function () {
    });
    $('#volume-handler').on('mouseup', function () {
        $('#volume-slider').off('mousemove');
    });


    function secondsToHHMMSS(seconds) {
        const date = new Date(seconds * 1000);
        return `${date.getUTCHours().toString().padStart(2, '0')}:${date.getUTCMinutes().toString().padStart(2, '0')}:${date.getUTCSeconds().toString().padStart(2, '0')}`;
    }

});