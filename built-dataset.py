import glob

# Get a list of all image files in the directory and its subdirectories
image_files = glob.glob('E:\dataset\cattle\BeefCattle_Muzzle\*\*', recursive=True)

# Print the list of image files
for image_file in image_files:
    print(image_file)