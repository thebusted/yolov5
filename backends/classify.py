from posixpath import splitext
import numpy as np
import cv2
import tensorflow as tf
import h5py
import os

from os.path import isdir, isfile, join

def classify(data):
    print('Classify data:', data)
    
    # Load model from file model_x_mobilenet.keras
    model = tf.keras.models.load_model(data['model'])
    
    print('model file:', data['model'])
    
    # Width model
    width = 128
    
    # File
    file = data['file']

    img = cv2.imread(file , cv2.COLOR_BGR2RGB)
    ori = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    img = cv2.resize(img ,(width,width))
    rImg = np.array(img)
    rImg = rImg.astype('float32')
    rImg /= 255
    rImg = np.reshape(rImg ,(1,width,width,3))
    predict = model.predict(rImg)
    
    # Print predict result
    probability = np.max(predict)
    
    # Hot encode to label
    hot_encode = np.argmax(predict)
    
    label = ['cattle_0100','cattle_0200']
    result = label[hot_encode]
    
    print('-------------------')
    print(predict)
    print('file:'+str(file))
    print('predict:'+str(result))
    print('-------------------')
    
    return {
        "predict": str(result),
        "probability": float(probability)
    }
    
def matching(data):
    model_path = data['model']
    file = data['file']
    resize = data['resize']
    
    # Result
    scores = []
    
    if not isdir(model_path) or not isfile(file):
        return {
            "scores": scores
        }
    
    # Get all file in model_path
    files = os.listdir(model_path)
    
    # Iterate files
    for i in range(len(files)):
        file = join(model_path, files[i])
        print("file:", file)
        
        # Get file name without extension
        
    
        model = h5py.File(file, 'r')
        descriptor = np.array(model['descriptor'])
        
        # Resize
        resize = 240
        if data['resize'] is not None:
            print("resize:", data['resize'])
            resize = int(data['resize'])
            
        # Check file test is not None
        if data['file'] is None or not isfile(data['file']):
            return {
                "score": 0
            }
            
        # Load the test image
        test_image = cv2.imread(data['file'])

        test_image = cv2.cvtColor(test_image, cv2.COLOR_BGR2RGB)

        # Resize the test image with width of 640 and automatic height
        test_image = cv2.resize(test_image, (resize, int(resize*test_image.shape[0]/test_image.shape[1])))
        
        test_image = cv2.cvtColor(test_image, cv2.COLOR_RGB2GRAY)

        sift = cv2.SIFT_create()

        test_keypoints, test_descriptor = sift.detectAndCompute(test_image, None)

        # Create a Brute Force Matcher object.
        bf = cv2.BFMatcher(cv2.NORM_L1, crossCheck = False)

        # Apply knn matching
        matches = bf.knnMatch(descriptor,test_descriptor,k=2)

        # Apply ratio test
        nearest = []
        for m,n in matches:
            if m.distance < 0.75*n.distance:
                nearest.append([m])
                
        total_descriptors = len(descriptor)
        total_nearest = len(nearest)
                
        print("Total descriptors:", total_descriptors)
        print("Good Matches:", total_nearest)
        
        similar_score = total_nearest / total_descriptors
        
        scores.append({
            "cid": splitext(files[i])[0],
            "total": total_descriptors,
            "score": total_nearest,
            "similar": similar_score
        })
        
    return scores

# Use SIFT to extract features from the image
def training(data):
    # Get the file of the training data
    file = data['file']
    
    # Get rancher ID
    uid = data['uid']
    
    # Get cattle ID
    cid = data['cid']
    
    # Get the resize
    resize = data['resize']
    
    # Store path
    store = data['store']
    
    # Check the path is not None
    if file is None or not isfile(file):
        return {
            "success": False,
            "message": "File not found"
        }
    
    # Load the image
    train_image = cv2.imread(file)

    train_image = cv2.cvtColor(train_image, cv2.COLOR_BGR2RGB)

    # Resize the image with width of 640 and automatic height
    train_image = cv2.resize(train_image, (resize, int(resize*train_image.shape[0]/train_image.shape[1])))

    # Convert the training image to gray scale
    train_image = cv2.cvtColor(train_image, cv2.COLOR_RGB2GRAY)
    
    # Create the SIFT object
    sift = cv2.SIFT_create()

    keypoints, descriptor = sift.detectAndCompute(train_image, None)
    
    # Folder to save the model
    model_file = f"{store}/{cid}.h5"
    
    # Save
    if os.path.exists(model_folder) == False:
        os.makedirs(model_folder)
    if os.path.isfile(model_file):
        os.remove(model_file)
    with h5py.File(model_file, 'w') as hf:
        hf.create_dataset('descriptor', data=descriptor)
    
    return {
        "success": True,
        "message": "Training success"
    }