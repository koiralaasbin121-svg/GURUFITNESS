import React, { useEffect, useRef, useState } from 'react';
import * as faceapi from 'face-api.js';
import { Camera, ShieldCheck, ShieldAlert, UserPlus, Fingerprint, Loader2 } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const FaceVerifier = () => {
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const [modelsLoaded, setModelsLoaded] = useState(false);
    const [isCameraActive, setIsCameraActive] = useState(false);
    const [status, setStatus] = useState('initializing'); // initializing, ready, detecting, verifying, success, error
    const [registeredFace, setRegisteredFace] = useState(null);
    const [message, setMessage] = useState('Loading Security Modules...');

    useEffect(() => {
        const loadModels = async () => {
            const MODEL_URL = '/models';
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                ]);
                setModelsLoaded(true);
                setStatus('ready');
                setMessage('Security Systems Ready');
            } catch (err) {
                console.error("Model loading error:", err);
                setStatus('error');
                setMessage('Failed to load security modules');
            }
        };
        loadModels();
    }, []);

    const startCamera = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480, facingMode: 'user' }
            });
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                setIsCameraActive(true);
                setStatus('detecting');
                setMessage('Scanning for Presence...');
            }
        } catch (err) {
            console.error("Camera error:", err);
            setStatus('error');
            setMessage('Camera Access Denied');
        }
    };

    const stopCamera = () => {
        if (videoRef.current && videoRef.current.srcObject) {
            const tracks = videoRef.current.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            videoRef.current.srcObject = null;
            setIsCameraActive(false);
            setStatus('ready');
            setMessage('Security Systems Ready');
        }
    };

    const handleDetection = async () => {
        if (!videoRef.current || !canvasRef.current || !isCameraActive) return;

        const displaySize = { width: 640, height: 480 };
        faceapi.matchDimensions(canvasRef.current, displaySize);

        const interval = setInterval(async () => {
            if (!isCameraActive) {
                clearInterval(interval);
                return;
            }

            const detections = await faceapi.detectAllFaces(
                videoRef.current,
                new faceapi.TinyFaceDetectorOptions()
            ).withFaceLandmarks().withFaceDescriptors();

            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            const ctx = canvasRef.current.getContext('2d');
            ctx.clearRect(0, 0, displaySize.width, displaySize.height);

            if (resizedDetections.length > 0) {
                // Draw face box with custom style
                resizedDetections.forEach(detection => {
                    const box = detection.detection.box;
                    ctx.strokeStyle = '#00f2fe';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);

                    // Draw corners
                    const cornerLen = 20;
                    ctx.beginPath();
                    ctx.moveTo(box.x, box.y + cornerLen);
                    ctx.lineTo(box.x, box.y);
                    ctx.lineTo(box.x + cornerLen, box.y);
                    ctx.stroke();

                    ctx.beginPath();
                    ctx.moveTo(box.x + box.width - cornerLen, box.y);
                    ctx.lineTo(box.x + box.width, box.y);
                    ctx.lineTo(box.x + box.width, box.y + cornerLen);
                    ctx.stroke();
                });

                if (status === 'detecting') {
                    setMessage('Face Detected');
                }
            } else {
                setMessage('Looking for Face...');
            }
        }, 100);

        return () => clearInterval(interval);
    };

    useEffect(() => {
        if (isCameraActive) {
            handleDetection();
        }
    }, [isCameraActive]);

    const enrollFace = async () => {
        if (!videoRef.current) return;
        setStatus('verifying');
        setMessage('Enrolling Face...');

        const detection = await faceapi.detectSingleFace(
            videoRef.current,
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptor();

        if (detection) {
            setRegisteredFace(detection.descriptor);
            setStatus('success');
            setMessage('Face Profile Registered');
            setTimeout(() => {
                setStatus('detecting');
                setMessage('Scan to Verify');
            }, 2000);
        } else {
            setMessage('Enrollment Failed: No Face Detected');
            setStatus('detecting');
        }
    };

    const verifyFace = async () => {
        if (!videoRef.current || !registeredFace) return;
        setStatus('verifying');
        setMessage('Verifying Identity...');

        const detection = await faceapi.detectSingleFace(
            videoRef.current,
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptor();

        if (detection) {
            const distance = faceapi.euclideanDistance(registeredFace, detection.descriptor);
            // Lower distance means better match. Threshold is typically 0.6
            if (distance < 0.5) {
                setStatus('success');
                setMessage('Identity Verified: Access Granted');
            } else {
                setStatus('error');
                setMessage('Verification Failed: Access Denied');
                setTimeout(() => {
                    setStatus('detecting');
                    setMessage('Try Again');
                }, 2000);
            }
        } else {
            setMessage('Verification Failed: No Face Detected');
            setStatus('detecting');
        }
    };

    return (
        <div className="app-container">
            <header style={{ textAlign: 'center', marginBottom: '2rem' }}>
                <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.6 }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '1rem', marginBottom: '1rem' }}>
                        <ShieldCheck size={48} className="gradient-text" style={{ color: 'var(--primary)' }} />
                        <h1 style={{ fontSize: '2.5rem' }}>GURU<span className="gradient-text">FITNESS</span> SECURITY</h1>
                    </div>
                    <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem' }}>Advanced Biometric Authentication System</p>
                </motion.div>
            </header>

            <main style={{ display: 'grid', gridTemplateColumns: '1fr 350px', gap: '2rem', alignItems: 'start' }}>
                <section className="glass-card" style={{ padding: '2rem' }}>
                    <div className="camera-container">
                        {!isCameraActive && (
                            <div style={{
                                position: 'absolute',
                                inset: 0,
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                zIndex: 5,
                                background: 'rgba(2, 6, 23, 0.8)'
                            }}>
                                <button className="btn btn-primary" onClick={startCamera}>
                                    <Camera size={20} /> Initialize Secure Feed
                                </button>
                            </div>
                        )}

                        {isCameraActive && <div className="scan-line"></div>}

                        <video
                            ref={videoRef}
                            autoPlay
                            muted
                            playsInline
                            className="camera-feed"
                        />
                        <canvas ref={canvasRef} className="face-overlay" />
                    </div>

                    <div style={{ marginTop: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                            <div className={`status-badge ${status === 'initializing' ? 'status-loading' :
                                    status === 'ready' || status === 'detecting' ? 'status-ready' :
                                        status === 'success' ? 'status-ready' : 'status-error'
                                }`}>
                                {status === 'initializing' && <Loader2 size={16} className="animate-spin" />}
                                {status === 'ready' && <ShieldCheck size={16} />}
                                {status === 'error' && <ShieldAlert size={16} />}
                                {status.toUpperCase()}
                            </div>
                            <span style={{ fontWeight: 500, color: 'var(--text-muted)' }}>{message}</span>
                        </div>

                        {isCameraActive && (
                            <button className="btn btn-secondary" onClick={stopCamera}>
                                Terminate Session
                            </button>
                        )}
                    </div>
                </section>

                <aside className="glass-card" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                    <h3 style={{ marginBottom: '1rem' }}>System Controls</h3>

                    <div style={{ padding: '1rem', background: 'var(--glass)', borderRadius: '16px', border: '1px solid var(--glass-border)' }}>
                        <h4 style={{ fontSize: '0.9rem', color: 'var(--text-muted)', marginBottom: '0.8rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <UserPlus size={16} /> FACE ENROLLMENT
                        </h4>
                        <p style={{ fontSize: '0.85rem', marginBottom: '1.2rem', opacity: 0.8 }}>
                            Capture your biometric data to create a secure identity profile.
                        </p>
                        <button
                            className="btn btn-secondary"
                            style={{ width: '100%' }}
                            onClick={enrollFace}
                            disabled={!isCameraActive || status === 'verifying'}
                        >
                            Enroll Biometrics
                        </button>
                    </div>

                    <div style={{ padding: '1rem', background: 'var(--glass)', borderRadius: '16px', border: '1px solid var(--glass-border)' }}>
                        <h4 style={{ fontSize: '0.9rem', color: 'var(--text-muted)', marginBottom: '0.8rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <Fingerprint size={16} /> IDENTITY VERIFICATION
                        </h4>
                        <p style={{ fontSize: '0.85rem', marginBottom: '1.2rem', opacity: 0.8 }}>
                            Verify identity against registered face profiles.
                        </p>
                        <button
                            className="btn btn-primary"
                            style={{ width: '100%' }}
                            onClick={verifyFace}
                            disabled={!isCameraActive || !registeredFace || status === 'verifying'}
                        >
                            Verify Profile
                        </button>
                        {!registeredFace && (
                            <p style={{ fontSize: '0.75rem', color: 'var(--error)', marginTop: '0.5rem', textAlign: 'center' }}>
                                Profile required for verification
                            </p>
                        )}
                    </div>

                    <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid var(--glass-border)' }}>
                        <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', textAlign: 'center' }}>
                            Secure Encrypted Connection • v1.0.4
                        </p>
                    </div>
                </aside>
            </main>

            <footer style={{ marginTop: '3rem', textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.9rem' }}>
                <p>&copy; 2026 GURUFITNESS Secure Systems. All biometric data remains on-device.</p>
            </footer>
        </div>
    );
};

export default FaceVerifier;
