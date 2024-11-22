import PropTypes from 'prop-types'
import { useCallback } from 'react'
import { useApiMutation } from 'hooks'
import { toast } from 'react-toastify'
import { downloadUrl, parseFile } from 'utilities'
import { apiV2 } from 'fetch-api'
import { Button } from 'kit'

export default function DataRequestConstituentDisplay({ iterationIndex, sentOrReceived, refreshMethod, fileTypes, dataRequestConstituent }) {
	const attachFileToRequestCall = useApiMutation(apiV2.dataRequestConstituent.post.attachment)
	const rejectAttachedFileCall = useApiMutation(apiV2.dataRequestConstituent.get.rejectAttachment)
	const acceptAttachedFileCall = useApiMutation(apiV2.dataRequestConstituent.get.acceptAttachment)
	const fileAttached = dataRequestConstituent.path && dataRequestConstituent.path !== ''

	const getReadableFileType = (typeId) => {
		for (const type of fileTypes.data) {
			if (Number(typeId) === Number(type.id)) {
				return type.extensions.length > 12
					? `${type.extensions.substr(0, 12)}...`
					: type.extensions
			}
		}
		return false
	}

	const getFileName = (name) => name.substr(name.lastIndexOf('/') + 1)

	const attachFile = useCallback(async () => {
		const fileObject = document.querySelector(`#dataFile_${dataRequestConstituent.id}`).files[0]
		const trueFileContent = await parseFile.toBase64(fileObject)

		const formData = {
			file: trueFileContent,
			fileMeta: {
				name: fileObject.name,
				type: fileObject.type,
				size: fileObject.size
			}
		}

		toast.promise(attachFileToRequestCall.callAsync(dataRequestConstituent.id, formData), {
			pending: 'Attaching file',
			success: 'File attached',
			error: 'Failed to attach file, please ensure the proper file type.'
		}).then((responseData) => {
			const { updatedDataRequest } = responseData.data
			refreshMethod(updatedDataRequest)
		})
	})

	const rejectAttachment = useCallback(() => {
		toast.promise(rejectAttachedFileCall.callAsync(dataRequestConstituent.id, null), {
			pending: 'Rejecting file',
			success: 'File Rejected',
			error: 'Failed to reject file'
		}).then((responseData) => {
			const { updatedDataRequest } = responseData.data
			refreshMethod(updatedDataRequest)
		})
	})

	const acceptAttachment = useCallback(() => {
		toast.promise(acceptAttachedFileCall.callAsync(dataRequestConstituent.id, null), {
			pending: 'Accepting file',
			success: 'File accepted',
			error: 'Failed to accept file'
		}).then((responseData) => {
			const { updatedDataRequest } = responseData.data
			refreshMethod(updatedDataRequest)
		})
	})

	const cellClass = (iterationIndex % 2 !== 0
		? 'px-5 bg-slate-100'
		: 'px-5'
	)

	const pathCellClasses = (
		dataRequestConstituent.accepted === false
			? `${cellClass} col-span-5 line-through`
			: `${cellClass} col-span-5`
	)

	const interactionButtonClass = `${cellClass} col-span-6 pb-3`

	const actionButtons = (
		sentOrReceived === 'sent'
			&& dataRequestConstituent.accepted === null
			&& dataRequestConstituent.path !== null
	)
		? (
			<>
				<div className={`${cellClass} col-span-3 pb-3`}>
					<Button className='w-full float-left' variant='success' size='sm' onClick={() => acceptAttachment(dataRequestConstituent.id)}>Accept</Button>
				</div>
				<div className={`${cellClass} col-span-3 pb-3`}>
					<Button className='w-full float-left' variant='danger' size='sm' onClick={() => rejectAttachment(dataRequestConstituent.id)}>Reject</Button>
				</div>
			</>
		)
		: ''

	let fileInteractionButton
	if (fileAttached) {
		fileInteractionButton = (
			<div className={interactionButtonClass}>
				<Button className='w-full' variant='outline' size='sm' onClick={() => downloadUrl(dataRequestConstituent.path, getFileName(dataRequestConstituent.path))}>Download</Button>
			</div>
		)
	}
	else if (sentOrReceived === 'received') {
		fileInteractionButton = (
			<div className={interactionButtonClass}>
				<input id={`dataFile_${dataRequestConstituent.id}`} type='file' onChange={() => attachFile()} />
			</div>
		)
	}
	else {
		fileInteractionButton = (
			<div className={interactionButtonClass}>
				<Button className='w-full' variant='outline' size='sm' disabled>Requested</Button>
			</div>
		)
	}

	return (
		<div className='grid grid-cols-6 text-xs shadow-[0_0_3px_1px_#CCC]'>
			<div className={`${cellClass} col-span-1`}>
				Label:
			</div>
			<div className={`${cellClass} col-span-5`}>
				{dataRequestConstituent.label}
			</div>
			<div className={`${cellClass} col-span-1`}>
				Type:
			</div>
			<div className={`${cellClass} col-span-5`}>
				{getReadableFileType(dataRequestConstituent.fileType) || 'Invalid file type'}
			</div>
			<div className={`${cellClass} col-span-1`} id={`constituent_path_${dataRequestConstituent.id}`}>
				Path:
			</div>
			<div className={pathCellClasses}>
				{dataRequestConstituent.path || 'N/A'}
			</div>
			<div className={`${cellClass} col-span-1`}>
				Last Updated:
			</div>
			<div className={`${cellClass} col-span-5`}>
				{dataRequestConstituent.updatedAt || 'N/A'}
			</div>
			<div className={`${cellClass} col-span-1`}>
				Created:
			</div>
			<div className={`${cellClass} col-span-5`}>
				{dataRequestConstituent.createdAt || 'N/A'}
			</div>
			{fileInteractionButton}
			{actionButtons}
		</div>
	)
}

DataRequestConstituentDisplay.propTypes = {
	iterationIndex: PropTypes.number.isRequired,
	sentOrReceived: PropTypes.string.isRequired,
	refreshMethod: PropTypes.func.isRequired,
	fileTypes: PropTypes.shape({
		data: PropTypes.any.isRequired
	}).isRequired,
	dataRequestConstituent: PropTypes.shape({
		id: PropTypes.number,
		label: PropTypes.string,
		fileType: PropTypes.any,
		accepted: PropTypes.bool,
		path: PropTypes.string,
		updatedAt: PropTypes.string,
		createdAt: PropTypes.string
	}).isRequired
}
