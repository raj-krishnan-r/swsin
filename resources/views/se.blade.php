<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>

<body>

    <h2>Sender</h2>
    <input type="number" id="regID" />
    <button onclick="register()">
        Register
    </button>
    <input type="number" id="recID" />
    <button onclick="makeCall()">
        Make call
    </button>
    <div id="offer" style="display: none;">
        There's a call.
        <button onclick="answer()">
            Answer ?
        </button>
    </div>
    <video autoplay id="viewfinder" style="width: 500px; height: 500px; border: 1px solid black;"></video>

    <script>
        var socket=null;
        function register() {
            socket.emit("register", document.getElementById('regID').value);
            mediaSanction();
        }
        const configuration = {
            'iceServers': [{
                'urls': 'turn:65.0.215.67:3478',
                'username': 'raj',
                'credential': '123456'
            }]
        };
        const peerConnection = new RTCPeerConnection(configuration);

        const constraints = {
            'video': true,
            'audio': true
        }


        async function mediaSanction() {
            const localStream = await navigator.mediaDevices.getUserMedia(constraints);
            //console.log('Got Media Stream',stream);
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        }

        async function makeCall() {

            socket.on('answer', async (message) => {
                if (message.answer) {
                    console.log('Remote Description set');
                    const remoteDesc = new RTCSessionDescription(message.answer);
                    await peerConnection.setRemoteDescription(remoteDesc);
                }
            });
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            let recipient = new Object();
            recipient.id = document.getElementById('recID').value;
            recipient.offer = offer;
            socket.emit('request', recipient);
            peerConnection.addEventListener('icegatheringstatechange', (event) => {});
            peerConnection.addEventListener('icecandidate', event => {
                if (event.candidate) {
                    console.log(event.candidate);
                    let package = new Object();
                    package.recID = document.getElementById('recID').value;
                    package.candidate = event.candidate;
                    socket.emit('ice-candidate', package);
                }
            });
            socket.on('ice-candidate', async (package) => {
                console.log('Candidate');
                try {
                    await peerConnection.addIceCandidate(package.candidate);
                } catch (e) {
                    console.error('Error adding ice candidate', e);
                }
            });
            peerConnection.addEventListener('connectionstatechange', event => {
                if (peerConnection.connectionState === 'connected') {
                    alert('Connected');
                }
            });
        }

        const remoteStream = new MediaStream();
        const remoteVideo = document.getElementById('viewfinder');
        remoteVideo.srcObject = remoteStream;

        peerConnection.addEventListener('track', async (event) => {
            remoteStream.addTrack(event.track, remoteStream);
        });
    </script>
    <script src="https://wesignal.herokuapp.com/socket.io/socket.io.js"></script>
    <script>
        socket = io('https://wesignal.herokuapp.com/');
    </script>

</body>

</html>