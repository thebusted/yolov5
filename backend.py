import platform
import time
import os

from backends.classify import matching, classify

# Check if the OS is Windows and change the pathlib.PosixPath to pathlib.WindowsPath
if platform.system() == "Windows":
    import pathlib
    temp = pathlib.PosixPath
    pathlib.PosixPath = pathlib.WindowsPath

from pathlib import Path
from backends.detect import detect

from fastapi import FastAPI, Query

app = FastAPI()

@app.get("/detect/{model}/{task_id}")
def run_predict(model: str, task_id: str, bucket: str = Query(None)):
    # Start time
    start_time = time.time()
    
    # Weight path merge with current_dir
    weight_path = Path(__file__).resolve().parent.joinpath('weights', 'detect', f"{model}.pt")
    
    print("bucket:", bucket)
    
    # Set result to None
    result = None
    
    # Check bucket is not None and is directory
    if bucket is not None and os.path.isdir(bucket):
        result = detect({
            "source": bucket,
            "weights": weight_path
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
    
@app.get("/identify/{uid}/{task_id}")
def run_predict(uid: str, task_id: str, file: str = Query(None)):
    # Start time
    start_time = time.time()
    
    # Model path
    model_path = Path(__file__).resolve().parent.joinpath('weights', 'identify', f"{uid}")
    
    print("file:", file)
    
    # Set result to None
    result = None
    
    # Check bucket is not None and is directory
    if file is not None and os.path.isfile(file):
        result = matching({
            "resize": 240,
            "file": file,
            "model": model_path
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
