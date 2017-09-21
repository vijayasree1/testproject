#<author> Nicki Kousted </author>
#<date>2014-11-27 </date>
#<note>Controller for the Master Switch feature</note>

#!/usr/bin/env python2.7

#<- Imports ->
#General.
import os, sys, time, paramiko
#Custom scripts.
import dbConnection, config
#XML.
from xml.etree import ElementTree


#Pull XMLfiles from Customer Login server, if any.
def pull_XML_files():
    try:
        #Create connection object.
        tunnel = dbConnection.SshTunnel(config.tunnel_Port, config.tunnel_User, config.tunnel_IP, config.tunnel_Password)
        (sftp, transport) = tunnel.open_sFTP_withPassword()
        #If source folder is on the Customer login server and the .
        if sftp.chdir(config.source) == None and os.path.exists(config.destination):
            #For each file and folder in the source dir.
            for file in sftp.listdir(config.source):
                #If file has an xml extension.
                if file.endswith(".xml"):
                     #Create unique paths for each XML file.
                     destinationPath = os.path.join(config.destination, file)
                     sourcePath = os.path.join(config.source, file)
                     #Get file(s) from server.
                     sftp.get(sourcePath, destinationPath)
                     #Remove remote file.
                     sftp.remove(sourcePath)
        else:
            raise Exception('Path exception for pulling XML file(s) - in the pull_XML_files()') 
        
    except Exception as e:
        raise Exception('in pull_XML_files() -> ' + str(e))

                
#Read files, which just have been placed in a specific folder.
def read_XML_files():
    try:
        dataList = []
        count = 0
        for file in os.listdir(config.destination):
            if file.endswith('.xml'):
                 #Create unique paths for each XML file.
                 destinationPath = os.path.join(config.destination, file)
                 #Parse xml structure into document variable.
                 document = ElementTree.parse(destinationPath)
                 #Find specific children.
                 dataList.append([])
                 dataList[count].append(document.find('Location').text)
                 dataList[count].append(document.find('Action').text)
                 dataList[count].append(document.find('User').text)
                 dataList[count].append(document.find('Date').text)
                 
                 #Count once up, when a XML file is found.
                 count += 1
                     
        #If no XML files are to process, quit else return.
        if count == 0:
            sys.exit(0)
        elif count >= 1:
            return dataList

    except Exception as e:
        raise Exception('in read_XML_files() -> ' + str(e))


#Rename file extensions to ensure, files are not read again when a new script runs.
def rename_Files(dataList):
    for i in dataList:
        var = os.path.join(config.destination, str(i[0]))
        if os.path.exists(var + '.xml'):
            #Change extension to ensure the file is not loaded by another MS script call during process.
            os.rename(var + '.xml', var + '.txt')
        elif os.path.exists(var + '.txt'):     
            #Change extension to ensure the file is not loaded by another MS script call during process.
            os.rename(var + '.txt', var + '.xml')
    
    
def insert_historical_log(dataList):
    try:        
        queryList = []
        for item in dataList:
            queryList.append('INSERT INTO cb_logging SET Location_Idx ='+ item[0] +', Status ='+ '"' +  item[1] +'"' + ', User ='+ '"' + item[2] +'"' + ', Date = '+ '"' + item[3] +'"' + ';')
        #Query database with historical log(s).
        mysqlDB.query_mySQL_write_many(queryList)
    
    except Exception as e:
        raise Exception('in insert_historical_log() -> ' + str(e))



def get_current_Status(results):
    try:
        queryListStatus = []
        for i in results:
            queryListStatus.append('SELECT Terminal_Idx, Status FROM cabinbilling WHERE Terminal_Idx =' + str(i[0]) + ';')

        #Reading all required queries.
        tmp_Return = mysqlDB.query_mySQL_read_many(queryListStatus)
            
        return tmp_Return
    
    except Exception as e:
        raise Exception('in get_current_Status() -> ' + str(e)) 


def revert_Changes(queryListUpdate, errorList):
    try:
        queryList = []
        for i in errorList:
            queryList.append('''SELECT location.Idx, service.Number, terminal.Idx, cabinbilling.Status, task.`Status`
            FROM location
            JOIN system_location_mapping ON location.Idx = system_location_mapping.Location_Idx
            JOIN system ON system_location_mapping.System_Idx = system.Idx
            JOIN terminal ON system.Idx = terminal.System_Idx
            JOIN task ON task.Idx = (SELECT task.Idx FROM task WHERE Terminal_Idx = terminal.idx AND (task.Entry_Date >= NOW() - INTERVAL 15 MINUTE)ORDER BY task.Entry_Date DESC LIMIT 1) 
            JOIN cabinbilling ON terminal.Idx = cabinbilling.Terminal_Idx
            JOIN service ON terminal.Idx = service.Terminal_Idx
            WHERE(system_location_mapping.End_Date > now() or system_location_mapping.End_Date IS NULL)
            AND terminal.Category = 'SBB' AND service.Service = 'STATIC_IP' AND service.Deactivation_Date IS NULL AND NOT terminal.Idx = ''' + str(i[0]) + ''' 
            AND location.Idx = (SELECT location.Idx
            FROM location
            JOIN system_location_mapping ON location.Idx = system_location_mapping.Location_Idx
            JOIN system ON system_location_mapping.System_Idx = system.Idx
            JOIN terminal ON system.Idx = terminal.System_Idx
            JOIN service ON terminal.Idx = service.Terminal_Idx
            WHERE (system_location_mapping.End_Date > now() or system_location_mapping.End_Date IS NULL)
            AND terminal.Category = 'SBB' AND service.Service = 'STATIC_IP' AND service.Deactivation_Date IS NULL AND terminal.Idx = ''' + str(i[0]) + ''' );''')

        #get All terminals under same location, which had the failed terminal.
        result_location = mysqlDB.query_mySQL_read_many(queryList)
            
        revertList = []
        modList = []

        if len(result_location) > 0:
            for row in result_location:
                if str(row[4]) != 'STATUS_DONE_FAIL':
                    #Add to revertlist.
                    if str(row[1]).startswith('10.11.'):
                        tVar = 'UPDATE cabinbilling SET Status = "Error_Disabling" WHERE Terminal_Idx =' + str(row[2]) + ';'
                        if tVar not in modList:
                            modList.append(tVar)
                            tmpList = [str(row[2]), str(row[1]), 'Enable']
                            revertList.append(tmpList)                  

                    elif str(row[1]).startswith('37.60.'):
                        eVar = 'UPDATE cabinbilling SET Status = "Error_Enabling" WHERE Terminal_Idx =' + str(row[2]) + ';'
                        if eVar not in modList:
                            modList.append(eVar)
                            tmpList = [str(row[2]), str(row[1]), 'Disable']
                            revertList.append(tmpList)
                        
            #remove reverted items.
            for a in list(queryListUpdate):
                s = ''.join(x for x in a if x.isdigit())
                for o in modList:
                    d = ''.join(x for x in o if x.isdigit())
                    if s == d:
                        queryListUpdate.remove(a)
                        
            #append reverted items.
            for x in modList:
                queryListUpdate.append(x)


        #If any siblings are added to the errorList.
        if len(revertList) > 0:
            #Append to task table.
            APN_results = insert_Task_Revert(revertList)
            #Call task processor, if any siblings are found.
            os.system(config.task_Path)
            #Wait for task table.
            time.sleep(config.task_Sleep)
            #Check task tables status.
            check_Task(APN_results, 0)
            
            #Return List
            return queryListUpdate
               
        else:
            #return List.
            return queryListUpdate

    except Exception as e:
        raise Exception('revertChanges() -> ' + str(e)) 


def insert_Task_Revert(revertList):
    try:     
        queryList = []
        for i in revertList:
            #Change current IP.
            #Ensure that correct IP fits to desired action.
            if i[2] == 'Enable':   
                mod_IP = '10.11.%s'%i[1][6:]
            elif i[2] == 'Disable':
                mod_IP = '37.60.%s'%i[1][6:]
                
            #create unique insert query.
            queryList.append('INSERT INTO task SET Task = "TASK_CHANGE_FIXED_IP", Terminal_Idx =' + str(i[0]) + ', Entry_Date= NOW() , Data1 ='+ '"' + mod_IP.strip() + '"' + ';')

        #Insert into task table.
        mysqlDB.query_mySQL_write_many(queryList)

        return revertList
        
    except Exception as e:
        raise Exception('in insert_Task_Revert() -> ' + str(e))

                 
#Insert into task table, which terminals IP wants changing.
def insert_Task(dataList):
    try:     
        queryList = []
        #Get all terminal(s) which is in the cb table(activated) for that specific location.
        for item in dataList:
            queryList.append('''SELECT DISTINCT terminal.Idx, service.Number, ''' + '"' + item[1] + '"' + ''' AS HStatus FROM location 
            JOIN system_location_mapping ON location.Idx = system_location_mapping.Location_Idx
            JOIN system ON system_location_mapping.System_Idx = system.Idx
            JOIN terminal ON system.Idx = terminal.System_Idx
            JOIN cabinbilling ON terminal.Idx = cabinbilling.Terminal_Idx
            JOIN service ON terminal.Idx = service.Terminal_Idx
            WHERE location.Idx = ''' + item[0] + '''
            AND (system_location_mapping.End_Date > now() or system_location_mapping.End_Date IS NULL)
            AND terminal.Category = 'SBB' AND service.Deactivation_Date IS NULL AND service.Service = 'STATIC_IP';''')

        #Query database for terminal data from location idx.
        result = mysqlDB.query_mySQL_read_many(queryList)
        
        queryListTask = []        
        for i in result:
            #Change current IP.
            #Ensure that correct IP fits to desired action.
            if i[2] == 'Enable':   
                mod_IP = '10.11.%s'%i[1][6:]
            elif i[2] == 'Disable':
                mod_IP = '37.60.%s'%i[1][6:]
                
            #create unique insert query.
            queryListTask.append('INSERT INTO task SET Task = "TASK_CHANGE_FIXED_IP", Terminal_Idx =' + str(i[0]) + ', Entry_Date= NOW() , Data1 ='+ '"' + mod_IP.strip() + '"' + ';')

        #Insert into task table.
        mysqlDB.query_mySQL_write_many(queryListTask)

        return result
        
    except Exception as e:
        raise Exception('in insert_Task() -> ' + str(e))


#Check if the task table succefully executed the update of the APN.
def check_Task(dataList, n):
    try:
        queryListCheck = []
        for i in dataList:
            queryListCheck.append('SELECT Terminal_Idx, Status, Data1 FROM task WHERE Terminal_Idx=' + str(i[0]) + ' AND (Entry_Date >= NOW() - INTERVAL 15 MINUTE) ORDER BY Entry_Date DESC LIMIT 1;')
        #Query database for updated data.
        results = mysqlDB.query_mySQL_read_many(queryListCheck)

        #Check if any are in NEW state still.
        for i in results:
            if n == config.apn_Basecase:
                #APN did not change.
                return results
            
            elif 'STATUS_DONE_NEW' == i[1]:
                #Give the taskprocessor time to complete, if many.
                time.sleep(config.apn_Sleep)
                #Recursive call.
                check_Task(dataList, n+1)
            
        return results
               
    except Exception as e:
        raise Exception('in check_Task() -> ' + str(e))


#Update local/main database.
def update_Local(results):
    try:
        errorList = []
        queryListUpdate = []
        for i in results:
            if 'STATUS_DONE_OK' == i[1]:
                #Update of APN was completed.
                if str(i[2]).startswith('10.11.'): 
                    queryListUpdate.append('UPDATE cabinbilling SET Status = "Enabled" WHERE Terminal_Idx =' + str(i[0]) + ';')
                elif str(i[2]).startswith('37.60.'):
                    queryListUpdate.append('UPDATE cabinbilling SET Status = "Disabled" WHERE Terminal_Idx =' + str(i[0]) + ';')
                else:
                    Exception('Abnormal insertion in update_Local() -> ')

            else:
                #Update failed.
                if str(i[2]).startswith('10.11.'):
                    queryListUpdate.append('UPDATE cabinbilling SET Status = "Error_Enabling" WHERE Terminal_Idx =' + str(i[0]) + ';')
                    errorList.append([str(i[0]), str(i[2])])
                elif str(i[2]).startswith('37.60.'):
                    queryListUpdate.append('UPDATE cabinbilling SET Status = "Error_Disabling" WHERE Terminal_Idx =' + str(i[0]) + ';')
                    errorList.append([str(i[0]), str(i[2])])


        #If any has status of NEW
        if len(errorList) > 0:
            mod_List = revert_Changes(queryListUpdate, errorList)
            #Insert into cabinbilling table on local server.
            mysqlDB.query_mySQL_write_many(mod_List)
        else:
            #Insert into cabinbilling table on local server.
            mysqlDB.query_mySQL_write_many(queryListUpdate)

        return errorList or "String"

    except Exception as e:
        raise Exception('in update_Local() -> ' + str(e))


def delete_Files(dataList):
    try:
        #Get location
        for i in dataList:
            var = os.path.join(config.destination, str(i[0]))
        #Delete txt file.
        os.remove(var + '.txt')

    except Exception as e:
        raise Exception('in delete_Files() -> ' + str(e))


#Push changes to the Customer Login server.
def push_Changes(dataList):
    try:
        #Create connection object.
        tunnel = dbConnection.SshTunnel(config.tunnel_Port, config.tunnel_User, config.tunnel_IP, config.tunnel_Password)
        #Open port forwarding tunnel, which forwards the original SSH traffic, so we can query the database.
        tunnel.open_forward_Tunnel()
        #mySQL object for the forwarded (foreign server) database.
        mysqlDBForward = dbConnection.mySQL_Controller(config.cluser, config.clhost, config.clpass, config.cldbname, config.tunnel_SQL_Port)
        mysqlDBForward.connect_mySQL()

        #Create insert sql statements for CL server.
        resultList = []
        for i in dataList:
            resultList.append('UPDATE cabinbilling SET Status =''"' + str(i[1]) + '"'' WHERE Terminal_Idx =' + str(i[0]) + ';')           

        #query Database.
        mysqlDBForward.query_mySQL_write_many(resultList)
       
    except Exception as e:
        raise Exception('in push_Changes() -> ' + str(e))
    else:
        #Close connections.
        if mysqlDBForward:
            mysqlDBForward.close_mySQL()
        #Tunnel fowarding closes itself.
        if tunnel:
            tunnel.close_Tunnel()

             
#Handles the flow and errorhandling.
def main():
    try:
        print "Pulling files.."
        #Step 1 - Pll all xml files from Customer login, deletes them if successful.
        pull_XML_files()
        
        print "Reading files.."
        #Step 2 - Read files
        dataList = read_XML_files()

    except Exception as e:            
        raise Exception('Failing reading/pulling files -> ' + str(e))

    #Open mySQL connection.
    try:
        #Create MySQL object.
        global mysqlDB
        mysqlDB = dbConnection.mySQL_Controller(config.localDb_User, config.localDb_IP, config.localDb_Pass, config.localDb_DB, config.localDb_Port)
        #Connect to mySQL database.
        mysqlDB.connect_mySQL()
        
    except Exception as e:            
        raise Exception('Connecting to the local database failed! ' + str(e))
    
    #Change file extension, so they wont get processed by other MS script calls.
    rename_Files(dataList)

    try:
        print "Inserting historical log.."
        #Step 3 - Insert new historical log.
        insert_historical_log(dataList)
        
        print "Inserting into task table..."
        #Step 4 - Insert new row in task table for each terminal, with the new IP - this updates the APN and after updates terminal table with ip.
        termList = insert_Task(dataList)
        
        #Call taskprocessor.py
        os.system(config.task_Path)

        print "Halting to let task processor complete.."
        #Give the taskprocessor time to complete, if many.
        #time.sleep(config.task_Sleep)
        
        print "Checking task status.."
        #Step 5 - Check if APN is updated correctly.
        resultAPN = check_Task(termList, 0)

        print "Updating local db.."
        #Step 6 - Update the CB structure (setting all activated terminals to enabled/disabled or error.
        error_list = update_Local(resultAPN)

        #Get current status of local db.
        statusResult = get_current_Status(resultAPN)

        #Close mySQL connection.
        mysqlDB.close_mySQL()

    except Exception as e:
        #Revert file extensions so they can be processed again.
        rename_Files(dataList)
        #Close mySQL connection.
        mysqlDB.close_mySQL()

        raise Exception('in MAIN()  local db or APN failed!-> ' + str(e))
    
    try: 
        print "Pushing changes to CL server"
        #Step 7 - Push update(s) to Customer Login.
        push_Changes(statusResult)
        
    except Exception as e:
        #Revert file extensions so they can be processed again.
        rename_Files(dataList)
        #Revert APN and local database.
        #Not implemented as it could bottleneck the task table if the CL server is down for a longer period.
    
        raise Exception('in MAIN() Updating CL database failed -> ' + str(e))

    else:
        #Delete txt files.
        delete_Files(dataList)

        #To send out error message if any APNs fail, so ISS can handle any errors.
        if isinstance(error_list, list):
            error_string = "Error(s) in APN FOUND, Please take action on following: "
            for i in error_list:
                error_string += str(i) +  " > "

            raise Exception(error_string)
                
                
                

