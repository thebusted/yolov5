import firebase_admin
from firebase_admin import credentials
from firebase_admin import db

cred = credentials.Certificate("./public/cattle-insurance-claim/_inc_/aiml-cattle-insurance-firebase-adminsdk-et09n-60c76cf2a5.json")

# Initialize the app with a service account, granting admin privileges
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://aiml-cattle-insurance-default-rtdb.asia-southeast1.firebasedatabase.app'
})

def rtdb_test():
    ref = db.reference('test/test').set({'fuck':'you'})