# Import required libraries
from flask import Flask, request, jsonify  # Flask: web framework, request: handle HTTP requests, jsonify: return JSON responses
import cv2  # OpenCV library for computer vision and image processing
import numpy as np  # Numerical computing library for array operations and math
import base64  # Encoding/decoding base64 strings (used for image data)
import os  # Operating system interface for file/directory operations
import pickle  # Serialize/deserialize Python objects to save/load data
from datetime import datetime  # For handling dates and timestamps

# Create Flask application instance
app = Flask(__name__)

# Define directory name where face data will be stored
FACE_DATA_DIR = "face_data"

# Check if face data directory exists, if not create it
if not os.path.exists(FACE_DATA_DIR):
    os.makedirs(FACE_DATA_DIR)  # Create the directory

# Load OpenCV's pre-trained Haar cascade classifier for face detection
# This is a machine learning model trained to detect faces in images
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

def get_face_encoding_path(user_id):
    
    #Generate file path for storing/loading face data for a specific user
    return os.path.join(FACE_DATA_DIR, f"{user_id}.pkl")

def extract_face_features(image):
    """
    Extract facial features from an image using computer vision techniques
    
    Args:
        image: Input image in BGR format (OpenCV default)
    
    Returns:
        numpy.array or None: Flattened array of face features or None if no face detected
    """
    # Convert color image to grayscale - reduces complexity and improves face detection
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    # Detect faces in the grayscale image
    # detectMultiScale parameters:
    # - gray: input grayscale image
    # - 1.3: scale factor (how much image size is reduced at each scale)
    # - 5: minimum neighbors (how many neighbors each candidate rectangle should have)
    faces = face_cascade.detectMultiScale(gray, 1.3, 5)
    
    # Check if any faces were detected
    if len(faces) == 0:
        return None  # No face found in the image
    
    # Get coordinates of the first detected face
    # faces[0] contains: x, y, width, height of bounding box
    x, y, w, h = faces[0]
    
    # Extract Region of Interest (ROI) - the face area from the grayscale image
    face_roi = gray[y:y+h, x:x+w]
    
    # Resize the face ROI to standard size (100x100 pixels)
    # This ensures all face encodings have the same dimensions for comparison
    face_standard = cv2.resize(face_roi, (100, 100))
    
    # Convert 2D image array (100x100) to 1D array (10,000 elements)
    flattened_face = face_standard.flatten()
    
    # Convert to float32 and normalize pixel values from 0-255 to 0.0-1.0
    # Normalization improves machine learning performance
    features = flattened_face.astype(np.float32) / 255.0
    
    return features  # Returns 10,000 normalized values representing the face

def compare_faces(features1, features2, threshold=0.8):
    """
    Compare two face feature vectors and determine if they match
    
    Args:
        features1: First face feature vector
        features2: Second face feature vector  
        threshold: Similarity threshold (0.0-1.0), higher = more strict matching
    
    Returns:
        bool: True if faces are similar enough, False otherwise
    """
    # Check if either feature vector is None (no face detected)
    if features1 is None or features2 is None:
        return False
    
    # Calculate Euclidean distance between the two feature vectors
    # Measures how different the two faces are
    distance = np.linalg.norm(features1 - features2)
    
    # Normalize the distance by dividing by maximum possible distance
    # sqrt(len(features1)) is the maximum distance between two normalized vectors
    normalized_distance = distance / np.sqrt(len(features1))
    
    # Convert distance to similarity score (1.0 = identical, 0.0 = completely different)
    similarity = 1 - normalized_distance
    
    # Return True if similarity exceeds threshold, False otherwise
    return similarity > threshold

@app.route('/register-face', methods=['POST'])
def register_face():
    """
    API endpoint to register a new face for a user
    
    Expected JSON payload:
    {
        "user_id": "123",
        "image": "base64_encoded_image_string"
    }
    
    Returns:
        JSON response with success status and message
    """
    try:
        # Extract JSON data from the POST request
        data = request.json
        user_id = data['user_id']  # Get user ID from request
        image_data = data['image']  # Get base64 encoded image string
        
        # Convert base64 string to binary image data
        image_bytes = base64.b64decode(image_data)
        
        # Convert binary data to numpy array of unsigned 8-bit integers
        image_array = np.frombuffer(image_bytes, np.uint8)
        
        # Decode the image array into OpenCV image format (BGR color space)
        image = cv2.imdecode(image_array, cv2.IMREAD_COLOR)
        
        # Check if image decoding was successful
        if image is None:
            return jsonify({'success': False, 'message': 'Invalid image'})
        
        # Extract facial features from the image
        features = extract_face_features(image)
        
        # Check if a face was detected and features extracted
        if features is None:
            return jsonify({'success': False, 'message': 'No face detected'})
        
        # Generate file path for storing this user's face data
        face_encoding_path = get_face_encoding_path(user_id)
        
        # Save the face features to a file using pickle serialization
        with open(face_encoding_path, 'wb') as f:  # 'wb' = write binary
            pickle.dump({
                'features': features,  # The actual face features
                'registered_at': datetime.now().isoformat()  # Timestamp of registration
            }, f)
        
        # Return success response
        return jsonify({
            'success': True, 
            'message': 'Face registered successfully'
        })
        
    except Exception as e:
        # Catch any errors and return error response
        return jsonify({
            'success': False, 
            'message': f'Error: {str(e)}'
        })

@app.route('/verify-face', methods=['POST'])
def verify_face():
    """
    API endpoint to verify a face against all registered faces
    
    Expected JSON payload:
    {
        "image": "base64_encoded_image_string" 
    }
    
    Returns:
        JSON response with success status and user_id if match found
    """
    try:
        # Extract JSON data from POST request
        data = request.json
        image_data = data['image']  # Get base64 encoded image
        
        # Convert base64 to OpenCV image (same process as register_face)
        image_bytes = base64.b64decode(image_data)
        image_array = np.frombuffer(image_bytes, np.uint8)
        image = cv2.imdecode(image_array, cv2.IMREAD_COLOR)
        
        # Validate image
        if image is None:
            return jsonify({'success': False, 'message': 'Invalid image'})
        
        # Extract features from the uploaded image
        uploaded_features = extract_face_features(image)
        
        # Check if face was detected
        if uploaded_features is None:
            return jsonify({'success': False, 'message': 'No face detected'})
        
        # Iterate through all registered face files in the face_data directory
        for filename in os.listdir(FACE_DATA_DIR):
            # Check if file is a pickle file (face data file)
            if filename.endswith('.pkl'):
                # Extract user_id from filename (remove .pkl extension)
                user_id = filename.split('.')[0]
                
                # Get full path to the face data file
                face_encoding_path = get_face_encoding_path(user_id)
                
                # Load the stored face data from file
                with open(face_encoding_path, 'rb') as f:  # 'rb' = read binary
                    stored_data = pickle.load(f)
                
                # Compare the uploaded face with the stored face
                if compare_faces(stored_data['features'], uploaded_features):
                    # Match found - return success with user_id
                    return jsonify({
                        'success': True, 
                        'user_id': user_id, 
                        'message': 'Face verified successfully'
                    })
        
        # No match found after checking all registered faces
        return jsonify({
            'success': False, 
            'message': 'Face not recognized'
        })
        
    except Exception as e:
        # Error handling
        return jsonify({
            'success': False, 
            'message': f'Error: {str(e)}'
        })

@app.route('/check-face-registered/<user_id>', methods=['GET'])
def check_face_registered(user_id):
    """
    API endpoint to check if a user has registered their face
    
    Args:
        user_id: User identifier from URL path
    
    Returns:
        JSON response indicating if face is registered
    """
    # Generate the expected file path for this user's face data
    face_encoding_path = get_face_encoding_path(user_id)
    
    # Check if the face data file exists
    if os.path.exists(face_encoding_path):
        return jsonify({'registered': True})  # Face is registered
    return jsonify({'registered': False})  # Face is not registered

@app.route('/health', methods=['GET'])
def health_check():
    """
    Health check endpoint to verify service is running
    
    Returns:
        JSON response with service status
    """
    return jsonify({
        'status': 'healthy', 
        'service': 'face_auth'
    })

# Main entry point - runs when script is executed directly
if __name__ == '__main__':
    # Print startup messages
    print("✅ Face Auth Service Starting on http://localhost:5000")
    print("✅ Using OpenCV face detection (no dlib required)")
    
    # Start the Flask development server
    # - host='0.0.0.0': makes server accessible from any IP
    # - port=5000: server listens on port 5000  
    # - debug=True: enables debug mode with auto-reload and detailed error pages
    app.run(host='0.0.0.0', port=5000, debug=True)




# ROI explaining :
# ┌─────────────────────────┐
# │                         │
# │    Background           │
# │                         │
# │      ┌─────────┐        │
# │      │  FACE   │        │  ← This rectangle is the ROI
# │      │  AREA   │        │
# │      └─────────┘        │
# │                         │
# │    More Background      │
# │                         │
# └─────────────────────────┘



# ┌─────────┐
# │  FACE   │  ← Only this part (the face region)
# │  AREA   │
# └─────────┘
